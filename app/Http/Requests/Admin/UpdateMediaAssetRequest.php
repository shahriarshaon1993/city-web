<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use App\Support\Media\MediaKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS-MED-004 detail drawer; limits follow DM-MASSET (VAL-01). Alt text is required for images
 * and SVGs unless "decorative" is ticked.
 */
class UpdateMediaAssetRequest extends FormRequest
{
    public const int MAX_TAGS = 20;

    public const int TAG_MAX = 40;

    public function authorize(): bool
    {
        /** @var MediaAsset $asset */
        $asset = $this->route('asset');

        return $this->user()->can('update', $asset);
    }

    protected function prepareForValidation(): void
    {
        $tags = collect((array) $this->input('tags', []))
            ->filter(is_string(...))
            ->map(fn (string $tag): string => trim((string) preg_replace('/\s+/u', ' ', $tag)))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->values()
            ->all();

        $this->merge(['tags' => $tags, 'is_decorative' => $this->boolean('is_decorative')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var MediaAsset $asset */
        $asset = $this->route('asset');
        $needsAlt = in_array($asset->kind, [MediaKind::Image, MediaKind::Svg], true);

        return [
            'title' => ['required', 'string', 'max:150'],
            'alt' => [$needsAlt ? 'required_unless:is_decorative,true' : 'nullable', 'nullable', 'string', 'max:125'],
            'is_decorative' => ['boolean'],
            'caption' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:120'],
            'tags' => ['array', 'max:'.self::MAX_TAGS],
            'tags.*' => ['string', 'max:'.self::TAG_MAX],
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'focal_x' => ['required', 'integer', 'between:0,100'],
            'focal_y' => ['required', 'integer', 'between:0,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alt.required_unless' => 'Describe the image for people who cannot see it, or tick “Decorative” if it adds nothing to the text.',
            'tags.max' => 'Use up to '.self::MAX_TAGS.' tags.',
            'tags.*.max' => 'Keep each tag under '.self::TAG_MAX.' characters.',
            'folder_id.exists' => 'That folder no longer exists.',
        ];
    }

    /**
     * @return array{title: string, alt: ?string, caption: ?string, credit: ?string, is_decorative: bool, tags: list<string>, folder_id: ?int, focal_x: int, focal_y: int}
     */
    public function metadata(): array
    {
        $text = fn (string $key): ?string => $this->filled($key) ? trim($this->string($key)->toString()) : null;

        return [
            'title' => trim($this->string('title')->toString()),
            'alt' => $text('alt'),
            'caption' => $text('caption'),
            'credit' => $text('credit'),
            'is_decorative' => $this->boolean('is_decorative'),
            'tags' => array_values((array) $this->input('tags', [])),
            'folder_id' => $this->integer('folder_id') ?: null,
            'focal_x' => $this->integer('focal_x'),
            'focal_y' => $this->integer('focal_y'),
        ];
    }
}
