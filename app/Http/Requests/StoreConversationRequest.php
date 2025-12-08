<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:direct,group',
            'title' => 'nullable|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type', 'direct');
            $participantIds = $this->participantIds();

            if (count($participantIds) === 0) {
                $validator->errors()->add('participant_ids', '参加者を1人以上選択してください。');
            }

            if ($type === 'direct' && count($participantIds) !== 1) {
                $validator->errors()->add('participant_ids', '1対1の会話は参加者を1人だけ選択してください。');
            }

            if ($type === 'group' && !$this->filled('title')) {
                $validator->errors()->add('title', 'グループ会話にはタイトルが必要です。');
            }
        });
    }

    public function participantIds(): array
    {
        $currentUserId = $this->user()?->id;

        $values = collect($this->input('participant_ids', []))
            ->map(static fn ($value) => is_numeric($value) ? (int) $value : null)
            ->filter(static fn ($value) => $value !== null)
            ->unique()
            ->reject(static fn ($value) => $currentUserId !== null && $value === $currentUserId)
            ->values()
            ->all();

        return $values;
    }

    public function conversationType(): string
    {
        return $this->input('type', 'direct');
    }
}
