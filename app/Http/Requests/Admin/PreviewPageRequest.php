<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Models\PageBlock;
use App\Support\Blocks\BlockType;
use App\Support\Seo\SeoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS-PB-002: the live preview asks for the blocks of an unsaved editor state, resolved like the
 * public site would draw them (600 ms after the last edit). Nothing is stored; only the envelope is
 * checked, because the point is to show invalid blocks too (CMS-PB-003).
 */
class PreviewPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->page();

        return $page instanceof Page
            ? $this->user()->can('update', $page)
            : $this->user()->can('create', Page::class);
    }

    public function page(): ?Page
    {
        return $this->filled('page_id') ? Page::query()->find($this->integer('page_id')) : null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:120'],
            'blocks' => ['present', 'array', 'max:'.Page::BLOCKS_MAX],
            'blocks.*.id' => ['required', 'integer'],
            'blocks.*.type' => ['required', 'string', Rule::in(BlockType::values())],
            'blocks.*.data' => ['present', 'array'],
            'blocks.*.is_visible' => ['required', 'boolean'],
            'blocks.*.anchor_id' => ['nullable', 'string', 'max:41'],
            'blocks.*.background' => ['required', 'string', Rule::in(PageBlock::BACKGROUNDS)],
            'blocks.*.spacing' => ['required', 'string', Rule::in(PageBlock::SPACINGS)],
            'blocks.*.animate' => ['required', 'boolean'],
            'seo' => SeoRules::rules()['seo'],
        ];
    }
}
