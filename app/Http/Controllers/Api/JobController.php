<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    /**
     * List all jobs with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = ServiceJob::with(['user', 'category']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by budget range
        if ($request->has('min_budget')) {
            $query->where('budget', '>=', $request->min_budget);
        }
        if ($request->has('max_budget')) {
            $query->where('budget', '<=', $request->max_budget);
        }

        // Sort by creation date by default
        $query->latest();

        $jobs = $query->paginate(15);

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    /**
     * Create a new job.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string'],
            'category_id' => ['required', 'exists:service_categories,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'preferred_date' => ['nullable', 'date', 'after:today'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:2048'], // max 2MB per image
        ]);

        $job = $request->user()->jobs()->create($request->except('images'));

        // Handle image uploads
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('jobs', 'public');
                $images[] = $path;
            }
            $job->update(['images' => $images]);
        }

        return response()->json([
            'message' => 'Job created successfully',
            'job' => $job->load(['user', 'category']),
        ], 201);
    }

    /**
     * Get a specific job.
     *
     * @param ServiceJob $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ServiceJob $job)
    {
        $job->load(['user', 'category', 'booking.fundi.user']);

        return response()->json([
            'job' => $job,
        ]);
    }

    /**
     * Update a job.
     *
     * @param Request $request
     * @param ServiceJob $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ServiceJob $job)
    {
        // Check if user owns the job
        if ($request->user()->id !== $job->user_id) {
            return response()->json([
                'message' => 'You can only update your own jobs',
            ], 403);
        }

        // Check if job can be updated
        if (!$job->isOpen()) {
            return response()->json([
                'message' => 'Cannot update a job that is not open',
            ], 403);
        }

        $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'exists:service_categories,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'preferred_date' => ['nullable', 'date', 'after:today'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:2048'],
        ]);

        $job->update($request->except('images'));

        // Handle image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($job->images) {
                foreach ($job->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('jobs', 'public');
                $images[] = $path;
            }
            $job->update(['images' => $images]);
        }

        return response()->json([
            'message' => 'Job updated successfully',
            'job' => $job->fresh(['user', 'category']),
        ]);
    }

    /**
     * Cancel a job.
     *
     * @param Request $request
     * @param ServiceJob $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, ServiceJob $job)
    {
        // Check if user owns the job
        if ($request->user()->id !== $job->user_id) {
            return response()->json([
                'message' => 'You can only cancel your own jobs',
            ], 403);
        }

        // Check if job can be cancelled
        if ($job->isCancelled()) {
            return response()->json([
                'message' => 'Job is already cancelled',
            ], 400);
        }

        if ($job->isCompleted()) {
            return response()->json([
                'message' => 'Cannot cancel a completed job',
            ], 400);
        }

        $job->update(['status' => 'cancelled']);

        // If job was booked, cancel the booking
        if ($job->booking) {
            $job->booking->cancel();
        }

        return response()->json([
            'message' => 'Job cancelled successfully',
            'job' => $job->fresh(['user', 'category']),
        ]);
    }

    /**
     * Get the authenticated user's jobs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myJobs(Request $request)
    {
        $query = $request->user()->jobs()
            ->with(['category', 'booking.fundi.user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate(15);

        return response()->json([
            'jobs' => $jobs,
        ]);
    }
} 