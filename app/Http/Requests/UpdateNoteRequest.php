<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('note'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'is_private' => ['boolean'],
        ];
    }
}
