<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Support\Blocks\BlockSanitizer;
use App\Support\Pages\PageRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The page editor (ADM-SCR-06): title, address, the block envelopes and the SEO panel, with the rules
 * of `PageRules`. A block's own data is not validated here: an invalid block is saved as a draft-only
 * block and blocks publishing (CMS-PB-003, VAL-05), so a half-finished page always saves.
 */
class SavePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        return $page instanceof Page
            ? $this->user()->can('update', $page)
            : $this->user()->can('create', Page::class);
    }

    protected function prepareForValidation(): void
    {
        $page = $this->route('page');

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'slug' => trim((string) $this->input('slug')),
            // A new page is always a builder page; the legal pages are system pages (DEC-89).
            'template' => $page instanceof Page ? $page->template : Page::BUILDER,
            // The builder adds client-only state (list keys, link state); it is never stored, not even in a revision.
            'blocks' => array_map(function (mixed $block): mixed {
                return is_array($block) && is_array($block['data'] ?? null)
                    ? [...$block, 'data' => BlockSanitizer::withoutEditorState($block['data'])]
                    : $block;
            }, is_array($this->input('blocks')) ? $this->input('blocks') : []),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $page = $this->route('page');

        return PageRules::rules($page instanceof Page ? $page : null);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return PageRules::attributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blocks.max' => 'A page can have at most '.Page::BLOCKS_MAX.' blocks.',
            'blocks.*.anchor_id.regex' => 'The section address must start with a letter and use only lowercase letters, numbers and hyphens.',
            'blocks.*.anchor_id.distinct' => 'Two blocks use the same section address.',
        ];
    }
}
