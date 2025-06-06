<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    /**
     * Get all jobs with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Job::with(['customer', 'category'])
            ->when($request->category_id, function ($query, $categoryId) {
                return $query->inCategory($categoryId);
            })
            ->when($request->status, function ($query, $status) {
                return $query->withStatus($status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $jobs = $query->latest()->paginate(10);

        return response()->json($jobs);
    }

    /**
     * Create a new job.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->isFundi()) {
            return response()->json(['message' => 'Fundis cannot create jobs'], 403);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:service_categories,id'],
        ]);

        $job = Job::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'category_id' => $request->category_id,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Job created successfully',
            'job' => $job->load(['customer', 'category']),
        ], 201);
    }

    /**
     * Get a specific job.
     *
     * @param Job $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Job $job)
    {
        $job->load(['customer', 'category', 'bookings.fundi']);

        return response()->json($job);
    }

    /**
     * Update a job.
     *
     * @param Job $job
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Job $job, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $job->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($job->status !== 'open') {
            return response()->json(['message' => 'Cannot update a job that is not open'], 400);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:service_categories,id'],
        ]);

        $job->update([
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'message' => 'Job updated successfully',
            'job' => $job->load(['customer', 'category']),
        ]);
    }

    /**
     * Cancel a job.
     *
     * @param Job $job
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Job $job, Request $request)
    {
        $user = $request->user();

        if ($user->id !== $job->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($job->status === 'closed') {
            return response()->json(['message' => 'Job is already closed'], 400);
        }

        $job->update(['status' => 'closed']);

        // Cancel any pending bookings
        $job->bookings()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Job cancelled successfully',
            'job' => $job->load(['customer', 'category']),
        ]);
    }

    /**
     * Get jobs created by the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myJobs(Request $request)
    {
        $user = $request->user();
        
        if ($user->isFundi()) {
            return response()->json(['message' => 'This endpoint is for customers only'], 403);
        }

        $jobs = Job::with(['category', 'bookings.fundi'])
            ->forCustomer($user->id)
            ->latest()
            ->paginate(10);

        return response()->json($jobs);
    }
} 