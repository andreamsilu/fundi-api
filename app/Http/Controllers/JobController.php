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
     * List all jobs (available jobs for everyone)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Job::with(['customer:id,full_name,phone,email', 'category:id,name', 'applications', 'media']);

            // Show all available jobs (public feed) - no filtering by user
            // This is the public job feed that everyone can see
            
            // Debug logging
            \Log::info('JobController index - User ID: ' . $user->id);
            \Log::info('JobController index - Query count before pagination: ' . $query->count());
            \Log::info('JobController index - Request URL: ' . $request->fullUrl());

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

            $paginator = $query->orderBy('created_at', 'desc')->paginate(15);

            // Shape response to match mobile expectations (consistent with FeedController)
            return response()->json([
                'success' => true,
                'message' => 'Jobs retrieved successfully',
                'data' => [
                    'jobs' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                ],
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
     * Get user's own jobs (for job owners to manage their jobs)
     */
    public function myJobs(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Job::with(['customer:id,full_name,phone,email', 'category:id,name', 'applications', 'media']);

            // Only show jobs owned by the current user
            $query->where('customer_id', $user->id);
            
            // Debug logging
            \Log::info('JobController myJobs - User ID: ' . $user->id);
            \Log::info('JobController myJobs - Query count before pagination: ' . $query->count());
            \Log::info('JobController myJobs - Request URL: ' . $request->fullUrl());

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
                    * sin(radians(location_lat)))) <= ?",
                    [$lat, $lng, $lat, $radius]
                );
            }

            $paginator = $query->orderBy('created_at', 'desc')->paginate(15);

            // Shape response to match mobile expectations
            return response()->json([
                'success' => true,
                'message' => 'My jobs retrieved successfully',
                'data' => [
                    'jobs' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve my jobs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving my jobs'
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

            if (!$user->isCustomer() && !$user->isAdmin()) {
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
                'location' => 'nullable|string|max:255',
                'location_lat' => 'nullable|numeric|between:-90,90',
                'location_lng' => 'nullable|numeric|between:-180,180',
                'urgency' => 'nullable|in:low,medium,high',
                'preferred_time' => 'nullable|string|max:100',
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
                'location' => $request->location,
                'location_lat' => $request->location_lat,
                'location_lng' => $request->location_lng,
                'urgency' => $request->urgency,
                'preferred_time' => $request->preferred_time,
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
                'location' => 'sometimes|string|max:255',
                'location_lat' => 'sometimes|numeric|between:-90,90',
                'location_lng' => 'sometimes|numeric|between:-180,180',
                'urgency' => 'sometimes|in:low,medium,high',
                'preferred_time' => 'sometimes|string|max:100',
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
                'location', 'location_lat', 'location_lng', 
                'urgency', 'preferred_time', 'status'
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
