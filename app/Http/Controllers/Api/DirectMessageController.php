<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageAttachmentService;
use App\Support\Presenters\ConversationPresenter;
use App\Support\Presenters\MessagePresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

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

        $conversationIds = $conversations->pluck('id')->all();
        $unreadCounts = $this->unreadCountsFor($user, $conversationIds);

        $conversations->each(static function (Conversation $conversation) use ($unreadCounts) {
            $conversation->setAttribute(
                'unread_count_for_viewer',
                (int) ($unreadCounts[$conversation->id] ?? 0)
            );
        });

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

        $conversation->participantRecords()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return response()->json(MessagePresenter::presentPaginator($messages));
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $total = $this->unreadCountsFor($user)->sum();

        return response()->json([
            'total' => (int) $total,
        ]);
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

    public function update(UpdateMessageRequest $request, Conversation $conversation, Message $message)
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user->id);
        $this->assertMessageBelongsToConversation($conversation, $message);
        $this->authorizeSender($message, $user->id);

        abort_if($message->is_deleted, Response::HTTP_BAD_REQUEST, '削除済みのメッセージは編集できません。');

        $body = $request->input('body');

        $message->forceFill([
            'body' => $body,
            'edited_at' => now(),
        ])->save();

        $message->loadMissing(['sender:id,name,display_name,avatar_url', 'attachments']);
        $conversation->touch();

        return response()->json(MessagePresenter::present($message));
    }

    public function destroy(Request $request, Conversation $conversation, Message $message)
    {
        $user = $request->user();
        $this->authorizeParticipant($conversation, $user->id);
        $this->assertMessageBelongsToConversation($conversation, $message);
        $this->authorizeSender($message, $user->id);

        if (!$message->is_deleted) {
            $message->forceFill([
                'is_deleted' => true,
                'deleted_at' => now(),
                'has_attachments' => false,
            ])->save();
        }

        $message->loadMissing(['sender:id,name,display_name,avatar_url']);
        $conversation->touch();

        return response()->json(MessagePresenter::present($message));
    }

    protected function unreadCountsFor(User $user, ?array $conversationIds = null): Collection
    {
        if ($conversationIds !== null && count($conversationIds) === 0) {
            return collect();
        }

        $query = Message::query()
            ->selectRaw('messages.conversation_id, COUNT(*) as unread_count')
            ->join('conversation_participants as cp', static function ($join) use ($user) {
                $join->on('cp.conversation_id', '=', 'messages.conversation_id')
                    ->where('cp.user_id', '=', $user->id);
            })
            ->where('messages.sender_id', '!=', $user->id)
            ->where(static function ($subQuery) {
                $subQuery
                    ->whereNull('cp.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'cp.last_read_at');
            });

        if ($conversationIds !== null) {
            $query->whereIn('messages.conversation_id', $conversationIds);
        }

        return $query
            ->groupBy('messages.conversation_id')
            ->pluck('unread_count', 'messages.conversation_id');
    }

    protected function authorizeParticipant(Conversation $conversation, int $userId): void
    {
        $exists = $conversation->participantRecords()
            ->where('user_id', $userId)
            ->exists();

        abort_unless($exists, Response::HTTP_FORBIDDEN, 'この会話にアクセスする権限がありません。');
    }

    protected function authorizeSender(Message $message, int $userId): void
    {
        abort_unless($message->sender_id === $userId, Response::HTTP_FORBIDDEN, '自分のメッセージのみ操作できます。');
    }

    protected function assertMessageBelongsToConversation(Conversation $conversation, Message $message): void
    {
        abort_unless(
            $message->conversation_id === $conversation->id,
            Response::HTTP_NOT_FOUND,
            'メッセージが見つかりません。'
        );
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
