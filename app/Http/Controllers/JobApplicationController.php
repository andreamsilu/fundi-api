<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplicationFee;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobApplicationController extends Controller
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Check if fundi can apply to a job.
     */
    public function checkApplicationEligibility(Request $request, Job $job): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can apply to jobs'
            ], 403);
        }

        $eligibility = $this->monetizationService->canFundiApplyToJob($fundi, $job);
        $applicationFee = $this->monetizationService->calculateApplicationFee($job);

        return response()->json([
            'success' => true,
            'data' => [
                'can_apply' => $eligibility['can_apply'],
                'reason' => $eligibility['reason'],
                'application_fee' => $applicationFee,
                'payment_type' => $eligibility['payment_type'] ?? null,
                'required_payment' => $eligibility['required_payment'] ?? null
            ]
        ]);
    }

    /**
     * Apply to a job with payment processing.
     */
    public function applyToJob(Request $request, Job $job): JsonResponse
    {
        $request->validate([
            'message' => 'sometimes|string|max:500',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'estimated_duration' => 'sometimes|string|max:100'
        ]);

        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can apply to jobs'
            ], 403);
        }

        // Check if job is still open
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer accepting applications'
            ], 400);
        }

        // Check if fundi has already applied
        $existingApplication = JobApplicationFee::where('job_id', $job->id)
            ->where('fundi_id', $fundi->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied to this job'
            ], 400);
        }

        // Process application payment
        $paymentResult = $this->monetizationService->processApplicationPayment($fundi, $job);

        if (!$paymentResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $paymentResult['message']
            ], 400);
        }

        // Create booking record
        $booking = DB::transaction(function () use ($fundi, $job, $request) {
            return $fundi->fundiBookings()->create([
                'job_id' => $job->id,
                'customer_id' => $job->user_id,
                'service_job_id' => $job->id,
                'description' => $request->message ?? 'Application submitted',
                'estimated_cost' => $request->estimated_cost,
                'estimated_duration' => $request->estimated_duration,
                'status' => 'pending',
                'payment_status' => 'paid' // Payment already processed
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data' => [
                'booking' => $booking,
                'application_fee' => $paymentResult['application_fee']
            ]
        ]);
    }

    /**
     * Get fundi's application history.
     */
    public function getApplicationHistory(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:pending,paid,refunded',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can view application history'
            ], 403);
        }

        $query = JobApplicationFee::where('fundi_id', $fundi->id)
            ->with(['job', 'creditTransaction', 'subscription'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 15);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * Get application statistics for fundi.
     */
    public function getApplicationStats(): JsonResponse
    {
        $fundi = Auth::user();
        
        if ($fundi->role !== 'fundi') {
            return response()->json([
                'success' => false,
                'message' => 'Only fundis can view application statistics'
            ], 403);
        }

        $stats = [
            'total_applications' => JobApplicationFee::where('fundi_id', $fundi->id)->count(),
            'paid_applications' => JobApplicationFee::where('fundi_id', $fundi->id)->paid()->count(),
            'pending_applications' => JobApplicationFee::where('fundi_id', $fundi->id)->pending()->count(),
            'total_fees_paid' => JobApplicationFee::where('fundi_id', $fundi->id)->paid()->sum('fee_amount'),
            'subscription_applications' => JobApplicationFee::where('fundi_id', $fundi->id)
                ->where('payment_type', 'subscription')
                ->count(),
            'credit_applications' => JobApplicationFee::where('fundi_id', $fundi->id)
                ->where('payment_type', 'credits')
                ->count(),
        ];

        // Monthly application fees for the last 6 months
        $monthlyFees = JobApplicationFee::where('fundi_id', $fundi->id)
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(fee_amount) as total_fees, COUNT(*) as application_count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stats['monthly_fees'] = $monthlyFees;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
