<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Upload portfolio media
     */
    public function uploadPortfolioMedia(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can upload portfolio media'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'portfolio_id' => 'required|exists:portfolio,id',
                'media_type' => 'required|in:image,video',
                'file' => 'required|file|max:10240', // 10MB max
                'order_index' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate file type based on media_type
            $file = $request->file('file');
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowedVideoTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];

            if ($request->media_type === 'image' && !in_array($file->getMimeType(), $allowedImageTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file type. Allowed: JPEG, PNG, GIF, WebP'
                ], 422);
            }

            if ($request->media_type === 'video' && !in_array($file->getMimeType(), $allowedVideoTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid video file type. Allowed: MP4, AVI, MOV, WMV'
                ], 422);
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $path = 'portfolio/' . $user->id . '/' . $filename;

            // Store file
            $storedPath = $file->storeAs('portfolio/' . $user->id, $filename, 'public');

            // Create database record
            $media = \App\Models\PortfolioMedia::create([
                'portfolio_id' => $request->portfolio_id,
                'media_type' => $request->media_type,
                'file_path' => $storedPath,
                'order_index' => $request->order_index ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'media_type' => $media->media_type,
                    'file_path' => $media->file_path,
                    'file_url' => Storage::url($storedPath),
                    'order_index' => $media->order_index,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while uploading media'
            ], 500);
        }
    }

    /**
     * Upload job media
     */
    public function uploadJobMedia(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only customers can upload job media'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|exists:jobs,id',
                'media_type' => 'required|in:image,video',
                'file' => 'required|file|max:10240', // 10MB max
                'order_index' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if job belongs to user
            $job = \App\Models\Job::where('id', $request->job_id)
                ->where('customer_id', $user->id)
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or unauthorized'
                ], 404);
            }

            // Validate file type based on media_type
            $file = $request->file('file');
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowedVideoTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];

            if ($request->media_type === 'image' && !in_array($file->getMimeType(), $allowedImageTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file type. Allowed: JPEG, PNG, GIF, WebP'
                ], 422);
            }

            if ($request->media_type === 'video' && !in_array($file->getMimeType(), $allowedVideoTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid video file type. Allowed: MP4, AVI, MOV, WMV'
                ], 422);
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $path = 'jobs/' . $user->id . '/' . $filename;

            // Store file
            $storedPath = $file->storeAs('jobs/' . $user->id, $filename, 'public');

            // Create database record
            $media = \App\Models\JobMedia::create([
                'job_id' => $request->job_id,
                'media_type' => $request->media_type,
                'file_path' => $storedPath,
                'order_index' => $request->order_index ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'media_type' => $media->media_type,
                    'file_path' => $media->file_path,
                    'file_url' => Storage::url($storedPath),
                    'order_index' => $media->order_index,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while uploading media'
            ], 500);
        }
    }

    /**
     * Upload fundi profile document (VETA certificate, etc.)
     */
    public function uploadProfileDocument(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isFundi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fundis can upload profile documents'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'document_type' => 'required|in:veta_certificate,id_copy,other',
                'file' => 'required|file|max:5120', // 5MB max for documents
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate file type for documents
            $file = $request->file('file');
            $allowedDocumentTypes = ['application/pdf', 'image/jpeg', 'image/png'];

            if (!in_array($file->getMimeType(), $allowedDocumentTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document file type. Allowed: PDF, JPEG, PNG'
                ], 422);
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $request->document_type . '_' . Str::uuid() . '.' . $extension;
            $path = 'documents/' . $user->id . '/' . $filename;

            // Store file
            $storedPath = $file->storeAs('documents/' . $user->id, $filename, 'public');

            // Update fundi profile with document path
            $fundiProfile = $user->fundiProfile;
            if ($fundiProfile) {
                if ($request->document_type === 'veta_certificate') {
                    $fundiProfile->update(['veta_certificate' => $storedPath]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'document_type' => $request->document_type,
                    'file_path' => $storedPath,
                    'file_url' => Storage::url($storedPath),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while uploading document'
            ], 500);
        }
    }

    /**
     * Delete media file
     */
    public function deleteMedia(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $media = \App\Models\PortfolioMedia::find($id);

            if (!$media) {
                // Try job media
                $media = \App\Models\JobMedia::find($id);
                if (!$media) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Media not found'
                    ], 404);
                }
            }

            // Check authorization
            if ($media instanceof \App\Models\PortfolioMedia) {
                if ($media->portfolio->fundi_id !== $user->id && !$user->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete this media'
                    ], 403);
                }
            } else {
                if ($media->job->customer_id !== $user->id && !$user->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete this media'
                    ], 403);
                }
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            // Delete database record
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting media'
            ], 500);
        }
    }

    /**
     * Get media file URL
     */
    public function getMediaUrl(Request $request, $id): JsonResponse
    {
        try {
            $media = \App\Models\PortfolioMedia::find($id);

            if (!$media) {
                // Try job media
                $media = \App\Models\JobMedia::find($id);
                if (!$media) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Media not found'
                    ], 404);
                }
            }

            $fileUrl = Storage::url($media->file_path);

            return response()->json([
                'success' => true,
                'message' => 'Media URL retrieved successfully',
                'data' => [
                    'id' => $media->id,
                    'media_type' => $media->media_type,
                    'file_path' => $media->file_path,
                    'file_url' => $fileUrl,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get media URL',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while getting media URL'
            ], 500);
        }
    }
}