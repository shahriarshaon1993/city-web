<?php

namespace App\Support\Blocks;

/**
 * One block type's contract (CMS-PB-003): the Laravel rules for the stored `data` (the editor shape),
 * which fields hold library asset ids, and how the stored data becomes the public props of the
 * matching `types/blocks.ts` type. The page stores only what an editor chose (asset ids, manual
 * project ids, a limit); `resolve` loads the rest, like an API Resource (ARC-002).
 */
abstract class BlockSchema
{
    /**
     * Rules for the keys of `data` (no prefix).
     *
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Public props of the block, from stored data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    abstract public function resolve(array $data, BlockContext $context): array;

    /**
     * Dotted paths of fields that hold library asset ids; `*` stands for every list item
     * (`tabs.*.image`, `images.*`). They become DM-MUSE usages of the page.
     *
     * @return list<string>
     */
    public function media(): array
    {
        return [];
    }

    /**
     * Dotted paths of rich-text fields, cleaned to the allowed tags when the block is saved
     * (NFR-SEC-03).
     *
     * @return list<string>
     */
    public function html(): array
    {
        return [];
    }

    /**
     * Human names for the messages, keyed like the rules.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Messages that replace Laravel's wording.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Rules that need the database or the whole block (a partner count, an alt text): path → message.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function checks(array $data): array
    {
        return [];
    }

    /**
     * The heading that becomes the page H1 when the block is first (CMS-PB-004); null for the others.
     *
     * @param  array<string, mixed>  $data
     */
    public function headline(array $data): ?string
    {
        return null;
    }
}
