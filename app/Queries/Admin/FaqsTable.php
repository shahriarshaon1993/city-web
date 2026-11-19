<?php

namespace App\Queries\Admin;

use App\Enums\FaqCategory;
use App\Http\Resources\Admin\FaqRowResource;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-09 FAQs: the questions in the order the FAQ page shows them inside each topic.
 *
 * @extends SocialTable<Faq>
 */
final class FaqsTable extends SocialTable
{
    public const string KEY = 'faqs';

    public function key(): string
    {
        return self::KEY;
    }

    public function url(): string
    {
        return route('admin.faqs.index');
    }

    protected function model(): string
    {
        return Faq::class;
    }

    protected function baseQuery(User $actor): Builder
    {
        return Faq::query()->withCount(['projects', 'locations', 'facetPages']);
    }

    protected function filters(): array
    {
        return [$this->statusFilter(), $this->enumFilter('category', 'category', FaqCategory::class)];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'question', 'category', 'is_published'];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = $this->likeTerm($term);

        $query->where(fn (Builder $query) => $query->where('question', 'like', $like)->orWhere('answer', 'like', $like));
    }

    protected function resource(): string
    {
        return FaqRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'question' => ['label' => 'Question', 'value' => fn (Faq $faq): string => $faq->question],
            'answer' => ['label' => 'Answer', 'value' => fn (Faq $faq): string => trim(html_entity_decode(strip_tags(str_replace(['</p>', '</li>'], ' ', $faq->answer))))],
            'category' => ['label' => 'Topic', 'value' => fn (Faq $faq): string => $faq->category->label()],
            'is_published' => ['label' => 'Published', 'value' => fn (Faq $faq): string => $faq->is_published ? 'Yes' : 'No'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return ['status' => $this->statusOptions(), 'category' => $this->enumOptions('category', FaqCategory::class)];
    }
}
