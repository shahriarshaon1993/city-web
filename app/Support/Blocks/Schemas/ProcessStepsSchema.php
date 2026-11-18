<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 16, CMP-BLK-STEPS: heading*, 2–8 steps, each with a title* and text*. */
final class ProcessStepsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'steps' => ['required', 'array', 'min:2', 'max:8'],
            'steps.*.title' => BlockRules::text(60, true),
            'steps.*.text' => BlockRules::text(300, true),
        ];
    }

    public function attributes(): array
    {
        return ['steps' => 'steps', 'steps.*.title' => 'step title', 'steps.*.text' => 'step text'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'steps' => array_map(fn (array $step): array => ['title' => (string) ($step['title'] ?? ''), 'text' => (string) ($step['text'] ?? '')], $data['steps'] ?? []),
        ];
    }
}
