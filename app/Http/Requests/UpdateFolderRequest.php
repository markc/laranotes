<?php

namespace App\Http\Requests;

use App\Models\Folder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('folder')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
            'sort_order' => ['sometimes', 'integer'],
            'is_private' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('is_private')) {
                return;
            }
            $folder = $this->route('folder');
            if (! $folder instanceof Folder) {
                return;
            }
            $wantsPrivate = $this->boolean('is_private');
            if (! $wantsPrivate || $folder->is_private) {
                return;
            }
            $hasOthersNotes = $folder->notes()
                ->where('user_id', '!=', $folder->user_id)
                ->exists();
            if ($hasOthersNotes) {
                $v->errors()->add('is_private', 'Cannot make folder private while it contains notes from other users.');
            }
        });
    }
}
