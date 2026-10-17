<?php

namespace App\Http\Requests\Api\V1\Chat;

class ChatMessagesRequest extends ChatPaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id,page'],
            'after_id' => ['nullable', 'integer', 'min:0', 'prohibits:before_id,page'],
        ];
    }

    public function usesCursor(): bool
    {
        return $this->filled('before_id') || $this->filled('after_id');
    }
}
