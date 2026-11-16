<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS-MED-008 "Replace everywhere", optionally followed by the delete the editor asked for.
 */
class ReplaceMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MediaAsset $asset */
        $asset = $this->route('asset');

        return $this->user()->can('update', $asset)
            && (! $this->boolean('delete_after') || $this->user()->can('delete', $asset));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'replacement_id' => ['required', 'integer', Rule::exists('media_assets', 'id')->whereNull('deleted_at')],
            'delete_after' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'replacement_id.required' => 'Pick the file that should take its place.',
            'replacement_id.exists' => 'That file is gone; pick another one.',
        ];
    }
}
