<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaFolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS-MED-002 folder create / rename / move. Depth, cycles and sibling names are the Actions'
 * checks (they come back as `parent_id` / `name` errors).
 */
class SaveMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $moving = $this->routeIs('admin.media.folders.move');

        return [
            'name' => [$creating || ! $moving ? 'required' : 'prohibited', 'string', 'max:'.MediaFolder::NAME_MAX, 'not_regex:/[\/\\\\]/'],
            'parent_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the folder a name.',
            'name.not_regex' => 'A folder name cannot contain / or \\.',
            'parent_id.exists' => 'That folder no longer exists.',
        ];
    }
}
