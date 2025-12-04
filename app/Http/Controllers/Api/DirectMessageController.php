<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\MessageAttachmentService;
use App\Support\Presenters\ConversationPresenter;
use App\Support\Presenters\MessagePresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DirectMessageController extends Controller
{
    public function __construct(private readonly MessageAttachmentService $attachmentService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', static function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'participants:id,name,display_name,avatar_url',
                'latestMessage.sender:id,name,display_name,avatar_url',
                'latestMessage.attachments',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'items' => ConversationPresenter::presentMany($conversations, $user),
        ]);
    }

    public function store(StoreConversationRequest $request)
    {
        $user = $request->user();
        $type = $request->conversationType();
        $participantIds = $request->participantIds();

        if ($type === 'direct' && count($participantIds) === 1) {
            $existing = $this->findExistingDirectConversation($user->id, $participantIds[0]);
            if ($existing) {
                $existing->loadMissing([
                    'participants:id,name,display_name,avatar_url',
                    'latestMessage.sender:id,name,display_name,avatar_url',
                    'latestMessage.attachments',
                ]);

                return response()->json(ConversationPresenter::present($existing, $user));
            }
        }

        $conversation = Conversation::create([
            'type' => $type,
            'title' => $type === 'group' ? $request->input('title') : null,
            'created_by' => $user->id,
        ]);

        $participants = [$user->id => ['role' => 'owner', 'joined_at' => now()]];
        foreach ($participantIds as $participantId) {
            $participants[$participantId] = ['role' => 'member', 'joined_at' => now()];
        }

        $conversation->participants()->attach($participants);
        $conversation->load([
            'participants:id,name,display_name,avatar_url',
            'latestMessage.sender:id,name,display_name,avatar_url',
            'latestMessage.attachments',
        ]);

        return response()->json(ConversationPresenter::present($conversation, $user), Response::HTTP_CREATED);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user->id);

        $perPage = (int) $request->input('perPage', 30);
        $perPage = max(1, min(100, $perPage));

        $messages = $conversation->messages()
            ->with(['sender:id,name,display_name,avatar_url', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json(MessagePresenter::presentPaginator($messages));
    }

    public function send(SendMessageRequest $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user->id);

        $attachments = $request->file('attachments', []);
        if (!is_array($attachments)) {
            $attachments = array_filter([$attachments]);
        }

        $hasAttachments = !empty($attachments);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->input('body'),
            'has_attachments' => $hasAttachments,
        ]);

        if ($hasAttachments) {
            $this->attachmentService->store($message, $attachments);
        }

        $message->load(['sender:id,name,display_name,avatar_url', 'attachments']);
        $conversation->touch();

        return response()->json(MessagePresenter::present($message), Response::HTTP_CREATED);
    }

    protected function authorizeParticipant(Conversation $conversation, int $userId): void
    {
        $exists = $conversation->participantRecords()
            ->where('user_id', $userId)
            ->exists();

        abort_unless($exists, Response::HTTP_FORBIDDEN, 'この会話にアクセスする権限がありません。');
    }

    protected function findExistingDirectConversation(int $currentUserId, int $otherUserId): ?Conversation
    {
        return Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', static function ($query) use ($currentUserId) {
                $query->where('users.id', $currentUserId);
            })
            ->whereHas('participants', static function ($query) use ($otherUserId) {
                $query->where('users.id', $otherUserId);
            })
            ->withCount('participants')
            ->having('participants_count', '=', 2)
            ->first();
    }
}
