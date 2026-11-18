<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use Illuminate\Validation\Rule;

/** Block 20, CMP-BLK-FAQ: source `category` (FR-SOC-003 categories) or `manual` picks; at least one FAQ. */
final class FaqSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'source' => BlockRules::choice(['category', 'manual']),
            'category' => ['required_if:source,category', 'nullable', 'string', Rule::in(BlockData::faqCategories())],
            'ids' => ['required_if:source,manual', 'nullable', 'array', 'max:30'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'questions', 'category' => 'FAQ category'];
    }

    public function checks(array $data): array
    {
        return $this->items($data) === [] ? ['source' => 'This block needs at least one published FAQ.'] : [];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'items' => array_map(
                fn (array $faq): array => ['id' => $faq['id'], 'question' => $faq['question'], 'answer' => $faq['answer']],
                $this->items($data),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function items(array $data): array
    {
        return ($data['source'] ?? 'category') === 'manual'
            ? BlockData::faqs(null, array_values(array_map(intval(...), $data['ids'] ?? [])))
            : BlockData::faqs((string) ($data['category'] ?? ''));
    }
}
