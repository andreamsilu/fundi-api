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
            'detailed_description' => ['nullable', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:service_categories,id'],
            'business_model' => ['required', 'string', 'in:c2c,b2c,c2b,b2b'],
            'job_type' => ['required', 'string'],
            'requirements' => ['nullable', 'array'],
            'skills_required' => ['nullable', 'array'],
            'certifications_required' => ['nullable', 'array'],
            'experience_required' => ['nullable', 'integer', 'min:0'],
            'tools_required' => ['nullable', 'array'],
            'insurance_required' => ['boolean'],
            'license_required' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'milestones' => ['nullable', 'array'],
            'onsite_required' => ['boolean'],
            'onsite_location' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['required', 'string', 'in:fixed,hourly,daily,milestone,negotiable'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'accepted_payment_methods' => ['nullable', 'array'],
            'payment_schedule' => ['nullable', 'string', 'in:immediate,net7,net15,net30,net60,net90,milestone,completion'],
            'requires_contract' => ['boolean'],
            'requires_invoice' => ['boolean'],
            'requires_insurance' => ['boolean'],
            'requires_license' => ['boolean'],
            'requires_background_check' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'urgency' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'deadline' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        // Check if user can be a client in this business model
        if (!$user->canBeClientInBusinessModel($request->business_model)) {
            return response()->json([
                'message' => 'You cannot create jobs in this business model with your current role and type'
            ], 403);
        }

        $job = Job::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'detailed_description' => $request->detailed_description,
            'location' => $request->location,
            'category_id' => $request->category_id,
            'status' => 'open',
            'business_model' => $request->business_model,
            'job_type' => $request->job_type,
            'requirements' => $request->requirements,
            'skills_required' => $request->skills_required,
            'certifications_required' => $request->certifications_required,
            'experience_required' => $request->experience_required,
            'tools_required' => $request->tools_required,
            'insurance_required' => $request->insurance_required ?? false,
            'license_required' => $request->license_required ?? false,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'milestones' => $request->milestones,
            'onsite_required' => $request->onsite_required ?? true,
            'onsite_location' => $request->onsite_location,
            'payment_type' => $request->payment_type,
            'budget_min' => $request->budget_min,
            'budget_max' => $request->budget_max,
            'fixed_amount' => $request->fixed_amount,
            'hourly_rate' => $request->hourly_rate,
            'daily_rate' => $request->daily_rate,
            'accepted_payment_methods' => $request->accepted_payment_methods,
            'payment_schedule' => $request->payment_schedule,
            'requires_contract' => $request->requires_contract ?? false,
            'requires_invoice' => $request->requires_invoice ?? false,
            'requires_insurance' => $request->requires_insurance ?? false,
            'requires_license' => $request->requires_license ?? false,
            'requires_background_check' => $request->requires_background_check ?? false,
            'tags' => $request->tags,
            'urgency' => $request->urgency ?? 'medium',
            'deadline' => $request->deadline,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
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