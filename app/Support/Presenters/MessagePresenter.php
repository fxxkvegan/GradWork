<?php

namespace App\Support\Presenters;

use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class MessagePresenter
{
    public static function present(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversationId' => $message->conversation_id,
            'body' => $message->body,
            'hasAttachments' => (bool) $message->has_attachments,
            'attachments' => $message->relationLoaded('attachments')
                ? $message->attachments->map(static fn (MessageAttachment $attachment) => self::presentAttachment($attachment))->all()
                : [],
            'sender' => self::presentSender($message->sender),
            'readAt' => optional($message->read_at)->toIso8601String(),
            'createdAt' => optional($message->created_at)->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<Message>  $messages
     */
    public static function presentMany(iterable $messages): array
    {
        $items = [];
        foreach ($messages as $message) {
            $items[] = self::present($message);
        }

        return $items;
    }

    public static function presentPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'items' => self::presentMany($paginator->items()),
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'prevPageUrl' => $paginator->previousPageUrl(),
        ];
    }

    protected static function presentSender(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
            'avatarUrl' => ProductPresenter::normalizePublicUrl($user->avatar_url),
        ];
    }

    protected static function presentAttachment(MessageAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'url' => Storage::disk('public')->url($attachment->path),
            'mime' => $attachment->mime,
            'size' => $attachment->size,
        ];
    }
}
