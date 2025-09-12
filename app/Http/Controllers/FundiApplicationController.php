<?php

namespace App\Http\Controllers;

use App\Models\FundiApplication;
use App\Models\FundiApplicationSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FundiApplicationController extends Controller
{
    /**
     * Submit a new fundi application
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is already a fundi
            if ($user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a fundi'
                ], 400);
            }

            // Check if user can become a fundi (must be a customer)
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can apply to become fundis'
                ], 400);
            }

            // Check if user already has a pending application
            $existingApplication = FundiApplication::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending fundi application'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:100',
                'phone_number' => 'required|string|max:20',
                'email' => 'required|email|max:100',
                'nida_number' => 'required|string|max:20|unique:fundi_applications,nida_number',
                'veta_certificate' => 'required|string|max:100',
                'location' => 'required|string|max:100',
                'bio' => 'required|string|min:50|max:1000',
                'skills' => 'required|array|min:1',
                'skills.*' => 'string|max:50',
                'languages' => 'required|array|min:1',
                'languages.*' => 'string|max:50',
                'portfolio_images' => 'nullable|array',
                'portfolio_images.*' => 'string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                $application = FundiApplication::create([
                    'user_id' => $user->id,
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'email' => $request->email,
                    'nida_number' => $request->nida_number,
                    'veta_certificate' => $request->veta_certificate,
                    'location' => $request->location,
                    'bio' => $request->bio,
                    'skills' => json_encode($request->skills),
                    'languages' => json_encode($request->languages),
                    'portfolio_images' => json_encode($request->portfolio_images ?? []),
                    'status' => 'pending',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Fundi application submitted successfully',
                    'data' => $application
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit fundi application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting application'
            ], 500);
        }
    }

    /**
     * Submit a specific section of the fundi application
     */
    public function submitSection(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is already a fundi
            if ($user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a fundi'
                ], 400);
            }

            // Check if user can become a fundi (must be a customer)
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can apply to become fundis'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'section_name' => 'required|string|in:personal_info,contact_info,professional_info,documents,portfolio',
                'section_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sectionName = $request->section_name;
            $sectionData = $request->section_data;

            // Validate section-specific data
            $sectionValidation = $this->validateSectionData($sectionName, $sectionData);
            if (!$sectionValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section validation failed',
                    'errors' => $sectionValidation['errors']
                ], 422);
            }

            // Create or update section
            $section = FundiApplicationSection::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'section_name' => $sectionName,
                ],
                [
                    'section_data' => $sectionData,
                    'is_completed' => true,
                    'submitted_at' => now(),
                ]
            );

            // Get updated progress
            $progress = FundiApplicationSection::getApplicationProgress($user->id);

            return response()->json([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $sectionName)) . ' section submitted successfully',
                'data' => [
                    'section' => $section,
                    'progress' => $progress,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit section',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting section'
            ], 500);
        }
    }

    /**
     * Get application progress for current user
     */
    public function getProgress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $progress = FundiApplicationSection::getApplicationProgress($user->id);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get application progress',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while getting progress'
            ], 500);
        }
    }

    /**
     * Get a specific section data
     */
    public function getSection(Request $request, $sectionName): JsonResponse
    {
        try {
            $user = $request->user();
            
            $section = FundiApplicationSection::where('user_id', $user->id)
                ->where('section_name', $sectionName)
                ->first();

            if (!$section) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Section not found'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $section
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get section',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while getting section'
            ], 500);
        }
    }

    /**
     * Submit final application after all sections are completed
     */
    public function submitFinalApplication(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is already a fundi
            if ($user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a fundi'
                ], 400);
            }

            // Check if all sections are completed
            if (!FundiApplicationSection::areAllSectionsCompleted($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete all required sections before submitting final application'
                ], 400);
            }

            // Check if user already has a pending application
            $existingApplication = FundiApplication::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending fundi application'
                ], 400);
            }

            // Get all section data
            $sections = FundiApplicationSection::where('user_id', $user->id)->get();
            $sectionData = $sections->pluck('section_data', 'section_name')->toArray();

            // Create final application
            $application = FundiApplication::create([
                'user_id' => $user->id,
                'full_name' => $sectionData['personal_info']['full_name'] ?? '',
                'phone_number' => $sectionData['contact_info']['phone_number'] ?? $user->phone,
                'email' => $sectionData['contact_info']['email'] ?? '',
                'nida_number' => $sectionData['documents']['nida_number'] ?? '',
                'veta_certificate' => $sectionData['documents']['veta_certificate'] ?? '',
                'location' => $sectionData['contact_info']['location'] ?? '',
                'bio' => $sectionData['professional_info']['bio'] ?? '',
                'skills' => $sectionData['professional_info']['skills'] ?? [],
                'languages' => $sectionData['professional_info']['languages'] ?? [],
                'portfolio_images' => $sectionData['portfolio']['portfolio_images'] ?? [],
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fundi application submitted successfully',
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit final application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while submitting application'
            ], 500);
        }
    }

    /**
     * Validate section-specific data
     */
    private function validateSectionData($sectionName, $data)
    {
        $rules = [];
        $messages = [];

        switch ($sectionName) {
            case 'personal_info':
                $rules = [
                    'full_name' => 'required|string|max:100',
                    'date_of_birth' => 'nullable|date',
                    'gender' => 'nullable|string|in:male,female,other',
                ];
                break;

            case 'contact_info':
                $rules = [
                    'phone_number' => 'required|string|max:20',
                    'email' => 'required|email|max:100',
                    'location' => 'required|string|max:100',
                    'address' => 'nullable|string|max:255',
                ];
                break;

            case 'professional_info':
                $rules = [
                    'bio' => 'required|string|min:50|max:1000',
                    'skills' => 'required|array|min:1',
                    'skills.*' => 'string|max:50',
                    'languages' => 'required|array|min:1',
                    'languages.*' => 'string|max:50',
                    'experience_years' => 'nullable|integer|min:0|max:50',
                ];
                break;

            case 'documents':
                $rules = [
                    'nida_number' => 'required|string|max:20',
                    'veta_certificate' => 'required|string|max:100',
                    'id_document' => 'nullable|string|max:255',
                    'certificate_document' => 'nullable|string|max:255',
                ];
                break;

            case 'portfolio':
                $rules = [
                    'portfolio_images' => 'nullable|array|max:10',
                    'portfolio_images.*' => 'string|max:255',
                    'portfolio_description' => 'nullable|string|max:500',
                ];
                break;
        }

        $validator = Validator::make($data, $rules, $messages);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()
        ];
    }

    /**
     * Get fundi application requirements
     */
    public function getRequirements(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'requirements' => [
                    'full_name' => 'Full legal name as per NIDA',
                    'phone_number' => 'Active phone number',
                    'email' => 'Valid email address',
                    'nida_number' => 'Valid NIDA number (20 digits)',
                    'veta_certificate' => 'VETA certificate number or file',
                    'location' => 'Current location/address',
                    'bio' => 'Professional bio (50-1000 characters)',
                    'skills' => 'List of skills (minimum 1 required)',
                    'languages' => 'Languages spoken (minimum 1 required)',
                    'portfolio_images' => 'Portfolio images (optional)',
                ],
                'process' => [
                    '1' => 'Submit application with all required documents',
                    '2' => 'Admin reviews application and documents',
                    '3' => 'Admin approves or rejects application',
                    '4' => 'If approved, customer gains fundi role and permissions',
                    '5' => 'If rejected, customer can reapply after addressing issues',
                ],
                'statuses' => [
                    'pending' => 'Application submitted, awaiting admin review',
                    'approved' => 'Application approved, user is now a fundi',
                    'rejected' => 'Application rejected, user can reapply',
                ]
            ]
        ]);
    }

    /**
     * Get current user's fundi application status
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $application = FundiApplication::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => true,
                    'message' => 'No fundi application found',
                    'data' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fundi application status retrieved successfully',
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi application status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving status'
            ], 500);
        }
    }

    /**
     * Get all fundi applications (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can view all applications'
                ], 403);
            }

            $query = FundiApplication::with('user');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('limit', 20);
            $applications = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Fundi applications retrieved successfully',
                'data' => $applications->items(),
                'total' => $applications->total(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fundi applications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving applications'
            ], 500);
        }
    }

    /**
     * Update application status (admin only)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can update application status'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected',
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $application = FundiApplication::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi application not found'
                ], 404);
            }

            DB::beginTransaction();

            try {
                $application->update([
                    'status' => $request->status,
                    'rejection_reason' => $request->rejection_reason,
                ]);

                // If approved, add fundi role to user (keeping existing roles)
                if ($request->status === 'approved') {
                    $user = $application->user;
                    $user->addRole('fundi');
                    
                    // Update user profile with fundi application data
                    $user->update([
                        'full_name' => $application->full_name,
                        'email' => $application->email,
                        'location' => $application->location,
                        'bio' => $application->bio,
                        'skills' => $application->skills,
                        'languages' => $application->languages,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Fundi application status updated successfully',
                    'data' => $application->fresh()
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fundi application status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating status'
            ], 500);
        }
    }

    /**
     * Delete fundi application
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $application = FundiApplication::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fundi application not found'
                ], 404);
            }

            // Users can only delete their own applications, admins can delete any
            if ($application->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own applications'
                ], 403);
            }

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fundi application deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fundi application',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting application'
            ], 500);
        }
    }
}
