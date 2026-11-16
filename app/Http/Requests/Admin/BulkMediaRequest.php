<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-009 bulk bar of the library: "Move to folder" (`folder_id` null = Unfiled) or Delete
 * (Recycle bin; used files are skipped and named, TOAST-06).
 */
class BulkMediaRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->input('action') === 'delete'
            ? $this->user()->canAny(['media.delete', 'media.delete-unused'])
            : $this->user()->can('media.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['move', 'delete'])],
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['folder_id.exists' => 'That folder no longer exists.'];
    }
}
