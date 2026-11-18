<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 26, CMP-BLK-FORM: heading*, intro, form type* (`contact`, `callback`, `landowner`, `nrb`), side content (rich). */
final class FormSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'intro' => BlockRules::text(300),
            'form_type' => BlockRules::choice(['contact', 'callback', 'landowner', 'nrb']),
            'side' => BlockRules::html(),
        ];
    }

    public function html(): array
    {
        return ['side'];
    }

    public function attributes(): array
    {
        return ['form_type' => 'form', 'side' => 'side text'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'intro' => ($data['intro'] ?? '') === '' ? null : $data['intro'],
            'form_type' => $data['form_type'] ?? 'contact',
            'side' => $data['side'] ?? null,
        ];
    }
}
