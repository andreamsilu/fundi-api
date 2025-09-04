<?php

namespace App\Http\Controllers;

use App\Models\FundiProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FundiController extends Controller
{
    /**
     * Get all fundis with their profiles.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::role(['fundi', 'businessProvider'])
            ->with(['fundiProfile', 'fundiProfile.serviceCategories'])
            ->when($request->category_id, function ($query, $categoryId) {
                return $query->whereHas('fundiProfile.serviceCategories', function ($q) use ($categoryId) {
                    $q->where('service_categories.id', $categoryId);
                });
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            });

        $fundis = $query->paginate(10);

        return response()->json($fundis);
    }

    /**
     * Get a specific fundi's profile.
     *
     * @param User $fundi
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $fundi)
    {
        if (!$fundi->canActAsFundi()) {
            return response()->json(['message' => 'User is not a fundi'], 404);
        }

        $fundi->load(['fundiProfile', 'fundiProfile.serviceCategories', 'reviews']);

        return response()->json($fundi);
    }

    /**
     * Update fundi's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canActAsFundi()) {
            return response()->json(['message' => 'User is not a fundi'], 403);
        }

        $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_available' => ['nullable', 'boolean'],
            'service_categories' => ['nullable', 'array'],
            'service_categories.*' => ['exists:service_categories,id'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $profile = $user->fundiProfile;

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($profile->profile_photo) {
                Storage::delete($profile->profile_photo);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $profile->profile_photo = $path;
        }

        $profile->bio = $request->bio ?? $profile->bio;
        $profile->location = $request->location ?? $profile->location;
        $profile->is_available = $request->is_available ?? $profile->is_available;
        $profile->save();

        if ($request->has('service_categories')) {
            $profile->serviceCategories()->sync($request->service_categories);
        }

        $user->load('fundiProfile.serviceCategories');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Get fundi's service categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServiceCategories(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canActAsFundi()) {
            return response()->json(['message' => 'User is not a fundi'], 403);
        }

        $categories = $user->fundiProfile->serviceCategories;

        return response()->json($categories);
    }

    /**
     * Update fundi's service categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServiceCategories(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canActAsFundi()) {
            return response()->json(['message' => 'User is not a fundi'], 403);
        }

        $request->validate([
            'service_categories' => ['required', 'array'],
            'service_categories.*' => ['exists:service_categories,id'],
        ]);

        $user->fundiProfile->serviceCategories()->sync($request->service_categories);

        return response()->json([
            'message' => 'Service categories updated successfully',
            'categories' => $user->fundiProfile->serviceCategories,
        ]);
    }
} 