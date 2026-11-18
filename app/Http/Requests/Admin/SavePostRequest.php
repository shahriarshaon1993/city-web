<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use App\Models\Post;
use App\Rules\UniqueSlug;
use App\Support\Media\MediaKind;
use App\Support\News\PostBody;
use App\Support\Seo\SeoRules;
use App\Support\Slugs\SlugAvailability;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The post editor (ADM-SCR-08, FR-NEWS-001): text, cover, category, tags, related projects and SEO. */
class SavePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post
            ? $this->user()->can('update', $post)
            : $this->user()->can('create', Post::class);
    }

    protected function prepareForValidation(): void
    {
        $tags = collect((array) $this->input('tags', []))
            ->map(fn (mixed $tag): string => trim((string) preg_replace('/\s+/u', ' ', (string) $tag)))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->values()
            ->all();

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'slug' => trim((string) $this->input('slug')),
            'excerpt' => trim((string) $this->input('excerpt')),
            'tags' => $tags,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $post = $this->route('post');
        $id = $post instanceof Post ? $post->id : null;

        return [
            'title' => ['required', 'string', 'min:3', 'max:'.Post::TITLE_MAX],
            'slug' => ['required', 'string', 'max:'.Post::TITLE_MAX, 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(Post::class, $id, 'post')],
            'excerpt' => ['nullable', 'string', 'max:'.Post::EXCERPT_MAX],
            'body' => ['nullable', 'string', 'max:300000', $this->bodyRule(...)],
            'cover_asset_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])->whereNull('deleted_at')],
            'category_id' => ['required', 'integer', Rule::exists('post_categories', 'id')->whereNull('deleted_at')],
            'author_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'is_featured' => ['boolean'],
            'tags' => ['nullable', 'array', 'max:'.Post::TAGS_MAX],
            'tags.*' => ['string', 'max:50'],
            'project_ids' => ['nullable', 'array', 'max:'.Post::PROJECTS_MAX],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            ...SeoRules::rules(),
        ];
    }

    /**
     * FR-NEWS-005 (heading order) and AC-NEWS-02 / FR-NEWS-006 (alt text and library images). Each problem is a
     * sentence the editor can act on.
     */
    private function bodyRule(string $attribute, mixed $value, Closure $fail): void
    {
        $html = (string) $value;

        foreach (PostBody::problems($html) as $problem) {
            $fail($problem);
        }

        $ids = PostBody::assetIds($html);

        if ($ids !== []) {
            $found = MediaAsset::query()->whereKey($ids)->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])->count();

            if ($found !== count($ids)) {
                $fail('An image in the article is no longer in the media library. Remove it or insert it again.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. year-end-offers.',
            'category_id.required' => 'Choose a category.',
            'tags.max' => 'Up to '.Post::TAGS_MAX.' tags.',
            'project_ids.max' => 'Pick up to '.Post::PROJECTS_MAX.' related projects.',
            'title.max' => 'The title can be at most '.Post::TITLE_MAX.' characters.',
            'excerpt.max' => 'The summary can be at most '.Post::EXCERPT_MAX.' characters.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cover_asset_id' => 'cover image',
            'category_id' => 'category',
            'author_user_id' => 'author',
            'project_ids' => 'related projects',
            'project_ids.*' => 'related project',
            'tags.*' => 'tag',
            'body' => 'article',
            ...SeoRules::attributes(),
        ];
    }
}
