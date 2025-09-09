<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Category;
use App\Services\PaymentValidationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * List all jobs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Job::with(['customer', 'category', 'applications']);

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by location (within radius)
            if ($request->has('lat') && $request->has('lng') && $request->has('radius')) {
                $lat = $request->lat;
                $lng = $request->lng;
                $radius = $request->radius; // in kilometers

                $query->whereRaw("
                    (6371 * acos(cos(radians(?)) 
                    * cos(radians(location_lat)) 
                    * cos(radians(location_lng) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(location_lat)))) <= ?
                ", [$lat, $lng, $lat, $radius]);
            }

            $jobs = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Jobs retrieved successfully',
                'data' => $jobs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve jobs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving jobs'
            ], 500);
        }
    }

    /**
     * Create a new job
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can create jobs'
                ], 403);
            }

            // Check payment requirements
            $paymentValidation = PaymentValidationService::canPostJob($user);
            
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
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string|max:150',
                'description' => 'required|string',
                'budget' => 'nullable|numeric|min:0',
                'deadline' => 'nullable|date|after:today',
                'location_lat' => 'nullable|numeric|between:-90,90',
                'location_lng' => 'nullable|numeric|between:-180,180',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $job = Job::create([
                'customer_id' => $user->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'budget' => $request->budget,
                'deadline' => $request->deadline,
                'location_lat' => $request->location_lat,
                'location_lng' => $request->location_lng,
            ]);

            $job->load(['customer', 'category']);

            // Log job creation
            AuditService::logCrud('CREATE', 'Job', $job->id, null, $job->toArray());

            $response = [
                'success' => true,
                'message' => 'Job created successfully',
                'data' => $job
            ];

            // Include payment information if fee is required
            if ($paymentValidation['fee_required']) {
                $response['payment_info'] = [
                    'fee_required' => true,
                    'fee_amount' => $paymentValidation['fee_amount'],
                    'payment_type' => $paymentValidation['payment_type'],
                    'message' => 'Payment required to activate this job'
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating job'
            ], 500);
        }
    }

    /**
     * Get a specific job
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $job = Job::with(['customer', 'category', 'applications.fundi.fundiProfile', 'media'])
                ->find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job retrieved successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving job'
            ], 500);
        }
    }

    /**
     * Update a job
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $job = Job::find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Only job owner or admin can update
            if ($job->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this job'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:150',
                'description' => 'sometimes|string',
                'budget' => 'sometimes|numeric|min:0',
                'deadline' => 'sometimes|date|after:today',
                'location_lat' => 'sometimes|numeric|between:-90,90',
                'location_lng' => 'sometimes|numeric|between:-180,180',
                'status' => 'sometimes|in:open,in_progress,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $job->update($request->only([
                'title', 'description', 'budget', 'deadline',
                'location_lat', 'location_lng', 'status'
            ]));

            $job->load(['customer', 'category']);

            return response()->json([
                'success' => true,
                'message' => 'Job updated successfully',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating job'
            ], 500);
        }
    }

    /**
     * Delete a job
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $job = Job::find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Only job owner or admin can delete
            if ($job->customer_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this job'
                ], 403);
            }

            $job->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting job'
            ], 500);
        }
    }
}
