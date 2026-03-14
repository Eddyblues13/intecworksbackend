<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * GET /chat/conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $threads = ChatThread::where('participant_a', $userId)
            ->orWhere('participant_b', $userId)
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json(
            $threads->map(fn ($t) => $t->toApiArray($userId))->values()
        );
    }

    /**
     * GET /chat/conversations/{chatThread}/messages
     */
    public function messages(ChatThread $chatThread, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$chatThread->hasParticipant($userId)) {
            return response()->json(['message' => 'Not a participant.'], 403);
        }

        $messages = ChatMessage::where('chat_thread_id', $chatThread->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json(
            $messages->getCollection()->map(fn ($m) => $m->toApiArray())
        );
    }

    /**
     * POST /chat/conversations/{chatThread}/messages
     */
    public function sendMessage(ChatThread $chatThread, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$chatThread->hasParticipant($userId)) {
            return response()->json(['message' => 'Not a participant.'], 403);
        }

        $request->validate([
            'content'     => 'required|string',
            'type'        => 'nullable|string|in:text,image,file',
            'attachments' => 'nullable|array',
        ]);

        $msg = ChatMessage::create([
            'chat_thread_id' => $chatThread->id,
            'sender_id'      => $userId,
            'content'        => $request->content,
            'type'           => $request->type ?? 'text',
            'attachments'    => $request->attachments,
        ]);

        $chatThread->update([
            'last_message'    => \Illuminate\Support\Str::limit($request->content, 100),
            'last_message_at' => Carbon::now(),
        ]);

        return response()->json($msg->toApiArray(), 201);
    }

    /**
     * POST /chat/conversations/{chatThread}/read
     */
    public function markAsRead(ChatThread $chatThread, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$chatThread->hasParticipant($userId)) {
            return response()->json(['message' => 'Not a participant.'], 403);
        }

        ChatMessage::where('chat_thread_id', $chatThread->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * POST /chat/conversations  (start new thread)
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'jobId'         => 'nullable|string',
            'participantId' => 'required|string|exists:users,id',
        ]);

        $userId     = $request->user()->id;
        $otherId    = (int) $request->participantId;
        $jobId      = $request->jobId ? (int) $request->jobId : null;

        // Normalize order
        $a = min($userId, $otherId);
        $b = max($userId, $otherId);

        $thread = ChatThread::firstOrCreate(
            ['participant_a' => $a, 'participant_b' => $b, 'service_job_id' => $jobId],
        );

        return response()->json($thread->toApiArray($userId), 201);
    }

    /**
     * POST /chat/messages/{chatMessage}/flag
     */
    public function flagMessage(ChatMessage $chatMessage, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $thread = $chatMessage->thread;

        if (!$thread || !$thread->hasParticipant($userId)) {
            return response()->json(['message' => 'Not a participant.'], 403);
        }

        $chatMessage->update(['is_flagged' => true]);
        return response()->json(['message' => 'Message flagged.']);
    }
}
