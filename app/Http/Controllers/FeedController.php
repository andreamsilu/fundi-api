<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FeedController extends Controller
{
    /**
     * Get fundi feed for customers (with approved and visible portfolio items)
     * Enhanced with stats, badges, and advanced filtering
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
            
            // Advanced filters
            $minHourlyRate = $request->get('min_hourly_rate', $request->get('minHourlyRate'));
            $maxHourlyRate = $request->get('max_hourly_rate', $request->get('maxHourlyRate'));
            $minExperience = $request->get('min_experience', $request->get('minExperience'));
            $verifiedOnly = $request->get('verified_only', $request->get('verifiedOnly'));
            $availableNow = $request->get('available_now', $request->get('availableNow'));
            
            // Sorting
            $sortBy = $request->get('sort_by', $request->get('sortBy', 'created_at'));
            $sortOrder = $request->get('sort_order', $request->get('sortOrder', 'desc'));

            // Generate cache key based on request parameters (for first page without filters)
            $hasFilters = $search || $location || $skills || $minRating || $minHourlyRate || 
                         $maxHourlyRate || $minExperience || $verifiedOnly || $availableNow || 
                         $sortBy !== 'created_at';
            
            $cacheKey = null;
            if ($page == 1 && !$hasFilters) {
                $cacheKey = 'fundi_feed_page_1_default';
                
                // Try to get from cache
                $cachedData = Cache::get($cacheKey);
                if ($cachedData) {
                    return response()->json($cachedData);
                }
            }

            // Optimized query with eager loading and counts
            $query = User::with([
                'visiblePortfolio' => function($q) {
                    $q->latest()->limit(3); // Only 3 latest for preview
                },
                'visiblePortfolio.media' => function($q) {
                    $q->limit(1); // Only first image per portfolio item
                },
                'fundiProfile'
            ])
            ->whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })
            ->where('status', 'active')
            // Add aggregate calculations
            ->withCount([
                'jobApplications as completed_jobs_count' => function($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->withAvg('ratingsReceived as average_rating', 'rating')
            ->withCount('ratingsReceived as total_ratings_count');

            // Apply search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Apply location filter
            if ($location) {
                $query->where(function($q) use ($location) {
                    $q->where('location', 'like', "%{$location}%")
                      ->orWhereHas('fundiProfile', function($q2) use ($location) {
                          $q2->where('location', 'like', "%{$location}%");
                      });
                });
            }

            // Apply skills filter
            if ($skills) {
                $skillsArray = is_array($skills) ? $skills : explode(',', $skills);
                $query->whereHas('fundiProfile', function($q) use ($skillsArray) {
                    foreach ($skillsArray as $skill) {
                        $q->where('skills', 'like', "%{$skill}%");
                    }
                });
            }

            // Apply minimum rating filter
            if ($minRating) {
                $query->having('average_rating', '>=', $minRating);
            }

            // Apply hourly rate filters
            if ($minHourlyRate || $maxHourlyRate) {
                $query->whereHas('fundiProfile', function($q) use ($minHourlyRate, $maxHourlyRate) {
                    if ($minHourlyRate) {
                        $q->where('hourly_rate', '>=', $minHourlyRate);
                    }
                    if ($maxHourlyRate) {
                        $q->where('hourly_rate', '<=', $maxHourlyRate);
                    }
                });
            }

            // Apply experience filter
            if ($minExperience) {
                $query->whereHas('fundiProfile', function($q) use ($minExperience) {
                    $q->where('experience_years', '>=', $minExperience);
                });
            }

            // Apply verified only filter
            if ($verifiedOnly) {
                $query->whereHas('fundiProfile', function($q) {
                    $q->where('verification_status', 'approved');
                });
            }

            // Apply availability filter
            if ($availableNow) {
                $query->whereHas('fundiProfile', function($q) {
                    $q->where('is_available', true);
                });
            }

            // Apply sorting
            switch($sortBy) {
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                case 'experience':
                    $query->orderByRaw("(SELECT experience_years FROM fundi_profiles WHERE fundi_profiles.user_id = users.id) {$sortOrder}");
                    break;
                case 'price':
                case 'hourly_rate':
                    $query->orderByRaw("(SELECT hourly_rate FROM fundi_profiles WHERE fundi_profiles.user_id = users.id) {$sortOrder}");
                    break;
                case 'reviews':
                    $query->orderBy('total_ratings_count', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
            }

            $paginator = $query->paginate((int) $perPage, ['*'], 'page', (int) $page);

            // Transform the data to include enhanced information
            $paginator->getCollection()->transform(function ($fundi) {
                $profile = $fundi->fundiProfile;
                
                // Parse skills for category extraction
                $skillsArray = is_string($profile->skills ?? '') 
                    ? json_decode($profile->skills, true) ?? []
                    : ($profile->skills ?? []);
                
                // Primary category is the first skill
                $primaryCategory = !empty($skillsArray) ? $skillsArray[0] : 'Skilled Fundi';
                
                return [
                    // Essential info
                    'id' => $fundi->id,
                    'name' => $fundi->full_name ?? $fundi->phone,
                    'email' => $fundi->email,
                    'phone' => $fundi->phone,
                    'profile_image' => $profile->profile_image ?? null,
                    'location' => $fundi->location ?? $profile->location ?? null,
                    'bio' => \Illuminate\Support\Str::limit($profile->bio ?? '', 150),
                    
                    // Category/Profession (derived from first skill)
                    'primary_category' => $primaryCategory,
                    'profession' => $primaryCategory,
                    
                    // Rating info
                    'average_rating' => round((float)($fundi->average_rating ?? 0), 1),
                    'total_ratings' => $fundi->total_ratings_count ?? 0,
                    
                    // Quick stats
                    'stats' => [
                        'completed_jobs' => $fundi->completed_jobs_count ?? 0,
                        'years_experience' => $profile->experience_years ?? 0,
                        'response_rate' => $profile->response_rate ?? 'N/A',
                    ],
                    
                    // Skills (parse JSON if stored as JSON string)
                    'top_skills' => array_slice($skillsArray, 0, 5),
                    
                    // Portfolio preview (limited to 3 items with 1 image each)
                    'portfolio_preview' => $fundi->visiblePortfolio->map(function($item) {
                        $firstMedia = $item->media->first();
                        return [
                            'id' => $item->id,
                            'thumbnail_url' => $firstMedia ? $firstMedia->file_url : null,
                        ];
                    })->take(3),
                    
                    // Full portfolio items for compatibility
                    'visible_portfolio' => $fundi->visiblePortfolio->map(function($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'description' => $item->description,
                            'skills_used' => $item->skills_used,
                            'media' => $item->media->map(function($media) {
                                return [
                                    'id' => $media->id,
                                    'media_type' => $media->media_type,
                                    'url' => $media->file_url,
                                ];
                            }),
                            'created_at' => $item->created_at,
                        ];
                    }),
                    
                    // Verification badges
                    'badges' => [
                        'is_verified' => ($profile->verification_status ?? 'pending') === 'approved',
                        'has_veta' => !empty($profile->veta_certificate),
                        'identity_verified' => !empty($fundi->nida_number),
                    ],
                    
                    // Availability and pricing
                    'is_available' => $profile->is_available ?? true,
                    'hourly_rate' => $profile->hourly_rate ?? null,
                    
                    // Fundi profile data for compatibility
                    'fundi_profile' => [
                        'id' => $profile->id ?? null,
                        'bio' => $profile->bio ?? null,
                        'skills' => $profile->skills ?? [],
                        'experience_years' => $profile->experience_years ?? 0,
                        'hourly_rate' => $profile->hourly_rate ?? null,
                        'verification_status' => $profile->verification_status ?? 'pending',
                        'is_available' => $profile->is_available ?? true,
                    ],
                    
                    // Timestamps
                    'created_at' => $fundi->created_at,
                    'updated_at' => $fundi->updated_at,
                ];
            });

            // Shape response to match mobile expectations
            $response = [
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
            ];

            // Cache the response if it's the first page without filters
            if ($cacheKey) {
                Cache::put($cacheKey, $response, now()->addMinutes(10)); // Cache for 10 minutes
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Failed to retrieve fundi feed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
                'name' => $fundi->full_name ?? $fundi->phone,
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
                        'name' => $fundi->full_name ?? $fundi->phone,
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

    /**
     * Get search suggestions for autocomplete
     */
    public function getSearchSuggestions(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $type = $request->get('type', 'fundi');
            $limit = $request->get('limit', 8);

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'suggestions' => []
                ]);
            }

            $suggestions = [];

            if ($type === 'fundi') {
                // Get fundi name suggestions
                $fundiNames = User::where('role', 'fundi')
                    ->where('full_name', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->pluck('full_name')
                    ->map(function ($name) {
                        return ['text' => $name, 'type' => 'fundi_name'];
                    });

                $suggestions = array_merge($suggestions, $fundiNames->toArray());

                // Get skill suggestions
                $skills = DB::table('user_skills')
                    ->join('skills', 'user_skills.skill_id', '=', 'skills.id')
                    ->where('skills.name', 'LIKE', "%{$query}%")
                    ->distinct()
                    ->limit($limit)
                    ->pluck('skills.name')
                    ->map(function ($skill) {
                        return ['text' => $skill, 'type' => 'skill'];
                    });

                $suggestions = array_merge($suggestions, $skills->toArray());

                // Get location suggestions
                $locations = User::where('role', 'fundi')
                    ->where('location', 'LIKE', "%{$query}%")
                    ->distinct()
                    ->limit($limit)
                    ->pluck('location')
                    ->map(function ($location) {
                        return ['text' => $location, 'type' => 'location'];
                    });

                $suggestions = array_merge($suggestions, $locations->toArray());
            } elseif ($type === 'job') {
                // Get job title suggestions
                $jobTitles = Job::where('title', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->pluck('title')
                    ->map(function ($title) {
                        return ['text' => $title, 'type' => 'job_title'];
                    });

                $suggestions = array_merge($suggestions, $jobTitles->toArray());

                // Get job category suggestions
                $categories = Job::join('categories', 'jobs.category_id', '=', 'categories.id')
                    ->where('categories.name', 'LIKE', "%{$query}%")
                    ->distinct()
                    ->limit($limit)
                    ->pluck('categories.name')
                    ->map(function ($category) {
                        return ['text' => $category, 'type' => 'category'];
                    });

                $suggestions = array_merge($suggestions, $categories->toArray());
            }

            // Remove duplicates and limit results
            $uniqueSuggestions = [];
            $seen = [];
            foreach ($suggestions as $suggestion) {
                if (!in_array($suggestion['text'], $seen)) {
                    $uniqueSuggestions[] = $suggestion;
                    $seen[] = $suggestion['text'];
                    if (count($uniqueSuggestions) >= $limit) {
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'suggestions' => $uniqueSuggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search suggestions: ' . $e->getMessage()
            ], 500);
        }
    }
}
