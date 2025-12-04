<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => 'nullable|string|max:4000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif|max:5120',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasBody = $this->filled('body');
            $hasFiles = $this->hasFile('attachments');

            if (!$hasBody && !$hasFiles) {
                $validator->errors()->add('body', 'メッセージ本文または画像を入力してください。');
            }
        });
    }
}
