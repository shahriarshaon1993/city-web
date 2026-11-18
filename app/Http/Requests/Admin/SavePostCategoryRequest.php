<?php

namespace App\Http\Requests\Admin;

use App\Models\PostCategory;
use App\Rules\UniqueSlug;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The category dialog (FR-NEWS-002): name, slug and the description shown on the category page. */
class SavePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof PostCategory
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', PostCategory::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => trim((string) $this->input('slug')),
            'description' => trim((string) $this->input('description')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $id = $category instanceof PostCategory ? $category->id : null;

        return [
            'name' => [
                'required', 'string', 'min:2', 'max:'.PostCategory::NAME_MAX,
                Rule::unique('post_categories', 'name')->ignore($id),
            ],
            'slug' => ['required', 'string', 'max:80', 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(PostCategory::class, $id, 'category')],
            'description' => ['nullable', 'string', 'max:'.PostCategory::DESCRIPTION_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'There is already a category with this name.',
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. buying-guides.',
        ];
    }
}
