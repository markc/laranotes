<?php

namespace App\Http\Requests;

use App\Models\Folder;
use App\Models\Note;
use Illuminate\Contracts\Validation\Validator;
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('folder_id')) {
                return;
            }
            $folderId = $this->input('folder_id');
            if ($folderId === null) {
                return;
            }
            $folder = Folder::find($folderId);
            if (! $folder) {
                return;
            }
            $note = $this->route('note');
            $authorId = $note instanceof Note ? $note->user_id : $this->user()->id;
            if ($folder->is_private && $folder->user_id !== $authorId) {
                $v->errors()->add('folder_id', 'Selected folder is not accessible to the note author.');
            }
        });
    }
}
