<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Rename a tag (FR-NEWS-003). A name another tag already has (any letter case) is refused: merge instead. */
class RenameTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tag = $this->route('tag');

        return $tag instanceof Tag && $this->user()->can('update', $tag);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('name')))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:'.Tag::NAME_MAX, Rule::unique('tags', 'name')->ignore($tag instanceof Tag ? $tag->id : null)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'Another tag already has this name. Merge the two tags instead.'];
    }
}
