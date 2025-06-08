<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all chats for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Chat::where(function ($query) {
            $query->where('user_id', Auth::id())
                  ->orWhere('fundi_id', Auth::id());
        })->with(['user', 'fundi', 'job', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }]);

        // Filter by job if provided
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        $chats = $query->latest('last_message_at')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $chats->items(),
            'meta' => [
                'current_page' => $chats->currentPage(),
                'last_page' => $chats->lastPage(),
                'per_page' => $chats->perPage(),
                'total' => $chats->total(),
            ]
        ]);
    }

    /**
     * Get a specific chat with its messages
     *
     * @param Chat $chat
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Chat $chat, Request $request): JsonResponse
    {
        // Ensure the user has access to this chat
        if ($chat->user_id !== Auth::id() && $chat->fundi_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $chat->messages()
            ->with('sender')
            ->latest()
            ->paginate($request->input('per_page', 50));

        // Mark unread messages as read
        $chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'chat' => $chat->load(['user', 'fundi', 'job']),
            'messages' => [
                'data' => $messages->items(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ]
            ]
        ]);
    }

    /**
     * Create a new chat
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fundi_id' => 'required|exists:users,id',
            'job_id' => 'nullable|exists:jobs,id',
            'message' => 'required|string|max:1000',
        ]);

        // Ensure the fundi exists and is actually a fundi
        $fundi = User::where('id', $request->fundi_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'fundi');
            })
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Find or create chat
            $chat = Chat::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'fundi_id' => $fundi->id,
                    'job_id' => $request->job_id,
                ],
                [
                    'last_message_at' => now(),
                ]
            );

            // Create initial message
            $message = $chat->messages()->create([
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'type' => 'text',
            ]);

            // Update last message timestamp
            $chat->update(['last_message_at' => now()]);

            // Send notification to the fundi
            $this->notificationService->send(
                $fundi,
                'chat.new_message',
                'New Message',
                'You have received a new message',
                ['chat_id' => $chat->id],
                $chat
            );

            DB::commit();

            return response()->json([
                'message' => 'Chat created successfully',
                'data' => $chat->load(['user', 'fundi', 'job', 'messages' => function ($query) {
                    $query->latest()->limit(1);
                }])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create chat'], 500);
        }
    }

    /**
     * Send a message in a chat
     *
     * @param Chat $chat
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Chat $chat, Request $request): JsonResponse
    {
        // Ensure the user has access to this chat
        if ($chat->user_id !== Auth::id() && $chat->fundi_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
            'type' => 'required|in:text,image,file',
            'file' => 'required_if:type,image,file|file|max:10240', // 10MB max
        ]);

        try {
            DB::beginTransaction();

            $messageData = [
                'sender_id' => Auth::id(),
                'type' => $request->type,
            ];

            // Handle file upload if present
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('chat-files/' . $chat->id, 'public');
                
                $messageData['message'] = Storage::url($path);
                $messageData['metadata'] = [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            } else {
                $messageData['message'] = $request->message;
            }

            // Create message
            $message = $chat->messages()->create($messageData);

            // Update last message timestamp
            $chat->update(['last_message_at' => now()]);

            // Send notification to the other party
            $recipient = $chat->user_id === Auth::id() ? $chat->fundi : $chat->user;
            $this->notificationService->send(
                $recipient,
                'chat.new_message',
                'New Message',
                'You have received a new message',
                ['chat_id' => $chat->id],
                $chat
            );

            DB::commit();

            return response()->json([
                'message' => 'Message sent successfully',
                'data' => $message->load('sender')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to send message'], 500);
        }
    }

    /**
     * Mark all messages in a chat as read
     *
     * @param Chat $chat
     * @return JsonResponse
     */
    public function markAsRead(Chat $chat): JsonResponse
    {
        // Ensure the user has access to this chat
        if ($chat->user_id !== Auth::id() && $chat->fundi_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All messages marked as read'
        ]);
    }

    /**
     * Get unread message count for all chats
     *
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        $count = ChatMessage::whereHas('chat', function ($query) {
            $query->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhere('fundi_id', Auth::id());
            });
        })
        ->where('sender_id', '!=', Auth::id())
        ->whereNull('read_at')
        ->count();

        return response()->json([
            'count' => $count
        ]);
    }
} 