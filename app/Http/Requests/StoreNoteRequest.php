<?php

namespace App\Http\Requests;

use App\Models\Folder;
use App\Models\Note;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Note::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'is_private' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $folderId = $this->input('folder_id');
            if ($folderId === null) {
                return;
            }
            $folder = Folder::find($folderId);
            if (! $folder) {
                return;
            }
            $authorId = $this->user()->id;
            if ($folder->is_private && $folder->user_id !== $authorId) {
                $v->errors()->add('folder_id', 'Selected folder is not accessible to the note author.');
            }
        });
    }
}
