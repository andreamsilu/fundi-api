<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Services\PaymentValidationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
    /**
     * Apply for a job
     */
    public function apply(Request $request, $jobId): JsonResponse
    {
        try {
            $user = $request->user();

            // Allow fundis and admins to apply for jobs
            if (!$user->isFundi() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis and admins can apply for jobs'
                ], 403);
            }

            $job = Job::find($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            if ($job->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'Job is not open for applications'
                ], 400);
            }

            // Check if fundi already applied
            $existingApplication = JobApplication::where('job_id', $jobId)
                ->where('fundi_id', $user->id)
                ->first();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already applied for this job'
                ], 400);
            }

            // Check payment requirements
            $paymentValidation = PaymentValidationService::canApplyForJob($user, $job);
            
            if (!$paymentValidation['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $paymentValidation['reason'],
                    'payment_required' => true,
                    'payment_details' => [
                        'fee_amount' => $paymentValidation['fee_amount'],
                        'payment_type' => $paymentValidation['payment_type'] ?? 'subscription'
                    ]
                ], 402); // Payment Required
            }

            $validator = Validator::make($request->all(), [
                'requirements' => 'nullable|string',
                'budget_breakdown' => 'required|array',
                'budget_breakdown.materials' => 'required|numeric|min:0',
                'budget_breakdown.labor' => 'required|numeric|min:0',
                'budget_breakdown.transport' => 'nullable|numeric|min:0',
                'estimated_time' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $application = JobApplication::create([
                'job_id' => $jobId,
                'fundi_id' => $user->id,
                'requirements' => $request->requirements,
                'budget_breakdown' => $request->budget_breakdown,
                'estimated_time' => $request->estimated_time,
            ]);

            $application->load(['fundi.fundiProfile', 'job']);

            // Log application creation
            AuditService::logCrud('CREATE', 'JobApplication', $application->id, null, $application->toArray());

            $response = [
                'success' => true,
                'message' => 'Application submitted successfully',
                'data' => $application
            ];

            // Include payment information if fee is required
            if ($paymentValidation['fee_required']) {
                $response['payment_info'] = [
                    'fee_required' => true,
                    'fee_amount' => $paymentValidation['fee_amount'],
                    'payment_type' => $paymentValidation['payment_type'],
                    'message' => 'Payment required to activate this application'
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting application'
            ], 500);
        }
    }

    /**
     * Get applications for a job
     */
    public function getJobApplications(Request $request, $jobId): JsonResponse
    {
        try {
            $user = $request->user();
            $job = Job::find($jobId);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Only job owner or admin can view applications
            if ($job->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view applications'
                ], 403);
            }

            $applications = JobApplication::with(['fundi.fundiProfile'])
                ->where('job_id', $jobId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Applications retrieved successfully',
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve applications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving applications'
            ], 500);
        }
    }

    /**
     * Get fundi's applications
     */
    public function getMyApplications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isFundi() && !$user->isCustomer() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis and customers can view their applications'
                ], 403);
            }

            $query = JobApplication::with(['job.customer', 'job.category']);
            
            if ($user->isFundi()) {
                // Fundis see applications they made
                $query->where('fundi_id', $user->id);
            } elseif ($user->isCustomer()) {
                // Customers see applications for their jobs
                $query->whereHas('job', function($q) use ($user) {
                    $q->where('customer_id', $user->id);
                });
            } elseif ($user->isAdmin()) {
                // Admins see all applications
                // No additional where clause needed
            }

            $applications = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Applications retrieved successfully',
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve applications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving applications'
            ], 500);
        }
    }

    /**
     * Accept or reject an application (Admin/Customer)
     */
    public function updateApplicationStatus(Request $request, $applicationId): JsonResponse
    {
        try {
            $user = $request->user();
            $application = JobApplication::with('job')->find($applicationId);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            // Only job owner or admin can update application status
            if ($application->job->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update application status'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:accepted,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $application->update(['status' => $request->status]);

            // If accepted, update job status
            if ($request->status === 'accepted') {
                $application->job->update(['status' => 'in_progress']);
            }

            $application->load(['fundi.fundiProfile', 'job']);

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating application status'
            ], 500);
        }
    }

    /**
     * Delete an application
     */
    public function destroy(Request $request, $applicationId): JsonResponse
    {
        try {
            $user = $request->user();
            $application = JobApplication::find($applicationId);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            // Only application owner or admin can delete
            if ($application->fundi_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this application'
                ], 403);
            }

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting application'
            ], 500);
        }
    }
}
