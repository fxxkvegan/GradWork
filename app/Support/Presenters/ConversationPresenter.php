<?php

namespace App\Support\Presenters;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;

class ConversationPresenter
{
    public static function present(Conversation $conversation, User $viewer): array
    {
        $participants = $conversation->participants instanceof Collection
            ? $conversation->participants
            : collect();
        $unreadCount = (int) ($conversation->getAttribute('unread_count_for_viewer') ?? 0);

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'title' => $conversation->title,
            'displayName' => self::displayName($conversation, $viewer, $participants),
            'participants' => $participants->map(static fn (User $user) => self::presentParticipant($user))->all(),
            'lastMessage' => $conversation->relationLoaded('latestMessage') && $conversation->latestMessage
                ? MessagePresenter::present($conversation->latestMessage)
                : null,
            'updatedAt' => optional($conversation->updated_at)->toIso8601String(),
            'createdAt' => optional($conversation->created_at)->toIso8601String(),
            'unreadCount' => $unreadCount,
        ];
    }

    /**
     * @param  iterable<Conversation>  $conversations
     */
    public static function presentMany(iterable $conversations, User $viewer): array
    {
        $items = [];
        foreach ($conversations as $conversation) {
            $items[] = self::present($conversation, $viewer);
        }

        return $items;
    }

    protected static function presentParticipant(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
            'avatarUrl' => ProductPresenter::normalizePublicUrl($user->avatar_url),
        ];
    }

    protected static function displayName(Conversation $conversation, User $viewer, Collection $participants): ?string
    {
        if ($conversation->type === 'group') {
            return $conversation->title ?? 'グループチャット';
        }

        $other = $participants->first(static fn (User $user) => $user->id !== $viewer->id);
        if ($other instanceof User) {
            return $other->display_name ?: $other->name;
        }

        return $conversation->title;
    }
}
