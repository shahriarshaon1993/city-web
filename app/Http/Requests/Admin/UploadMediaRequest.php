<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use App\Support\Media\UploadPurpose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One file per request (the library dropzone of 11.2 sends them one by one for per-file progress).
 * Type, size, pixel and hero checks are the UploadInspector's (CMS-MED-003, HERO-010, HERO-022) and
 * come back as the `file` error.
 */
class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MediaAsset::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'purpose' => ['nullable', Rule::enum(UploadPurpose::class)],
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a file to upload.',
            'file.uploaded' => 'The upload did not finish. The server accepts files up to '.ini_get('upload_max_filesize').'B; try a smaller file.',
            'folder_id.exists' => 'That folder no longer exists.',
        ];
    }

    public function purpose(): UploadPurpose
    {
        return $this->enum('purpose', UploadPurpose::class) ?? UploadPurpose::Library;
    }
}
