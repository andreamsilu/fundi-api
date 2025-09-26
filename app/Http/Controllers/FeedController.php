<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    /**
     * Get fundi feed for customers (with approved and visible portfolio items)
     */
    public function getFundiFeed(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Normalize query parameters (support both snake_case and camelCase used by mobile)
            $perPage = $request->get('per_page', $request->get('limit', 15));
            $page = $request->get('page', 1);
            $search = $request->get('search');
            $location = $request->get('location');
            $skills = $request->get('skills');
            $minRating = $request->get('min_rating', $request->get('minRating'));

            $query = User::with(['visiblePortfolio.media', 'fundiProfile'])
                ->whereHas('roles', function($q) {
                    $q->where('name', 'fundi');
                })
                ->where('status', 'active');

            // Apply search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Apply location filter (if location data is available)
            if ($location) {
                // This would need to be implemented based on your location data structure
                // For now, we'll skip location filtering
            }

            // Apply skills filter
            if ($skills) {
                $skillsArray = is_array($skills) ? $skills : explode(',', $skills);
                $query->whereHas('visiblePortfolio', function($q) use ($skillsArray) {
                    foreach ($skillsArray as $skill) {
                        $q->orWhere('skills_used', 'like', "%{$skill}%");
                    }
                });
            }

            // Apply minimum rating filter
            if ($minRating) {
                $query->whereHas('ratingsReceived', function($q) use ($minRating) {
                    $q->havingRaw('AVG(rating) >= ?', [$minRating]);
                });
            }

            $paginator = $query->orderBy('created_at', 'desc')
                ->paginate((int) $perPage, ['*'], 'page', (int) $page);

            // Transform the data to include portfolio items in the feed (safe serialization)
            $paginator->getCollection()->transform(function ($fundi) {
                $fundiData = $fundi->toArray();
                $fundiData['portfolio_items'] = $fundi->visiblePortfolio->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'skills_used' => $item->skills_used,
                        'media' => $item->media->map(function($media) {
                            return [
                                'id' => $media->id,
                                'media_type' => $media->media_type,
                                'file_url' => $media->file_url,
                            ];
                        }),
                        'created_at' => $item->created_at,
                    ];
                });
                return $fundiData;
            });

            // Shape response to match mobile expectations
            return response()->json([
                'success' => true,
                'message' => 'Fundi feed retrieved successfully',
                'data' => [
                    'fundis' => $paginator->items(),
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
                'message' => 'Failed to retrieve fundi feed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job feed for fundis
     */
    public function getJobFeed(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $perPage = $request->get('per_page', $request->get('limit', 15));
            $page = $request->get('page', 1);
            $search = $request->get('search');
            $category = $request->get('category');
            $minBudget = $request->get('min_budget', $request->get('minBudget'));
            $maxBudget = $request->get('max_budget', $request->get('maxBudget'));
            $location = $request->get('location');

            $query = Job::with(['customer:id,full_name,phone,email', 'category:id,name', 'media:id,job_id,media_type,file_path'])
                ->where('status', 'open');
                // Show all available jobs - no user filtering for public feed

            // Apply search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply category filter
            if ($category) {
                $query->where('category_id', $category);
            }

            // Apply budget filters
            if ($minBudget) {
                $query->where('budget', '>=', $minBudget);
            }
            if ($maxBudget) {
                $query->where('budget', '<=', $maxBudget);
            }

            // Apply location filter (if location data is available)
            if ($location) {
                // This would need to be implemented based on your location data structure
                // For now, we'll skip location filtering
            }

            $paginator = $query->orderBy('created_at', 'desc')
                ->paginate((int) $perPage, ['*'], 'page', (int) $page);

            // Shape response to match mobile expectations
            return response()->json([
                'success' => true,
                'message' => 'Job feed retrieved successfully',
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
                'message' => 'Failed to retrieve job feed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fundi profile details for customers
     */
    public function getFundiProfile(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $fundi = User::with(['visiblePortfolio.media', 'fundiProfile', 'ratingsReceived'])
                ->where('id', $id)
                ->whereHas('roles', function($q) {
                    $q->where('name', 'fundi');
                })
                ->where('status', 'active')
                ->first();

            if (!$fundi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi not found'
                ], 404);
            }

            // Calculate average rating
            $averageRating = $fundi->ratingsReceived()->avg('rating') ?? 0;
            $totalRatings = $fundi->ratingsReceived()->count();

            // Build response payload matching mobile model needs
            $profile = [
                'id' => (string) $fundi->id,
                'name' => $fundi->full_name ?? ($fundi->name ?? $fundi->phone),
                'email' => $fundi->email ?? '',
                'phone' => $fundi->phone,
                'profileImage' => optional($fundi->fundiProfile)->profile_image ?? null,
                'location' => $fundi->location ?? '',
                'rating' => round((float) $averageRating, 1),
                'totalJobs' => \App\Models\Job::whereHas('applications', function($q) use ($fundi) {
                    $q->where('fundi_id', $fundi->id)->where('status', 'accepted');
                })->count(),
                'completedJobs' => \App\Models\Job::whereHas('applications', function($q) use ($fundi) {
                    $q->where('fundi_id', $fundi->id)->where('status', 'completed');
                })->count(),
                'skills' => is_array($fundi->skills) ? $fundi->skills : [],
                'certifications' => array_filter([
                    optional($fundi->fundiProfile)->veta_certificate,
                ]),
                'nidaNumber' => $fundi->nida_number ?? null,
                'vetaCertificate' => optional($fundi->fundiProfile)->veta_certificate ?? null,
                'isVerified' => (optional($fundi->fundiProfile)->verification_status ?? 'pending') === 'approved',
                'isAvailable' => true,
                'bio' => $fundi->bio ?? optional($fundi->fundiProfile)->bio ?? null,
                'hourlyRate' => (float) (optional($fundi->fundiProfile)->hourly_rate ?? 0),
                'portfolio' => [
                    'items' => $fundi->visiblePortfolio?->map(function($p) {
                        return [
                            'id' => $p->id,
                            'title' => $p->title,
                            'description' => $p->description,
                            'skills_used' => $p->skills_used,
                            'media' => $p->media?->map(function($m) {
                                return [
                                    'id' => $m->id,
                                    'media_type' => $m->media_type,
                                    'file_url' => \Storage::url($m->file_path),
                                ];
                            }),
                            'created_at' => $p->created_at,
                        ];
                    }),
                ],
                'totalRatings' => $totalRatings,
                'createdAt' => $fundi->created_at,
                'updatedAt' => $fundi->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fundi profile retrieved successfully',
                'data' => $profile,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job details for fundis
     */
    public function getJobDetails(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $job = Job::with(['customer:id,full_name,phone,email', 'category:id,name', 'media:id,job_id,media_type,file_path'])
                ->where('id', $id)
                ->where('status', 'open')
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or not available'
                ], 404);
            }

            // Check if fundi has already applied for this job
            $hasApplied = $user->jobApplications()
                ->where('job_id', $id)
                ->exists();

            $job->has_applied = $hasApplied;

            return response()->json([
                'success' => true,
                'data' => $job,
                'message' => 'Job details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve job details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nearby fundis based on location
     */
    public function getNearbyFundis(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = \Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'integer|min:1|max:100' // radius in kilometers
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->get('radius', 10); // default 10km radius

            // This is a simplified version - you might want to use a proper geospatial query
            $fundis = User::with(['visiblePortfolio.media', 'fundiProfile'])
                ->whereHas('roles', function($q) {
                    $q->where('name', 'fundi');
                })
                ->where('status', 'active')
                ->get()
                ->filter(function ($fundi) use ($latitude, $longitude, $radius) {
                    // This would need proper geospatial calculation
                    // For now, we'll return all fundis
                    return true;
                })
                ->take(20) // Limit to 20 nearby fundis
                ->map(function ($fundi) {
                    // Safe serialization for nearby fundis
                    return [
                        'id' => $fundi->id,
                        'name' => $fundi->name,
                        'phone' => $fundi->phone,
                        'email' => $fundi->email,
                        'fundi_profile' => $fundi->fundiProfile ? [
                            'id' => $fundi->fundiProfile->id,
                            'full_name' => $fundi->fundiProfile->full_name,
                            'bio' => $fundi->fundiProfile->bio,
                            'skills' => $fundi->fundiProfile->skills,
                            'experience_years' => $fundi->fundiProfile->experience_years,
                        ] : null,
                        'portfolio_items' => $fundi->visiblePortfolio->map(function($item) {
                            return [
                                'id' => $item->id,
                                'title' => $item->title,
                                'description' => $item->description,
                                'skills_used' => $item->skills_used,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $fundis,
                'message' => 'Nearby fundis retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nearby fundis: ' . $e->getMessage()
            ], 500);
        }
    }
}
