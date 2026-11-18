<?php

namespace App\Support\Blocks\Schemas;

use App\Models\ProjectProgressUpdate;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use Illuminate\Validation\Rule;

/** Block 8, CMP-BLK-PROGRESS: 1–3 projects that have construction updates. */
final class ProgressHighlightSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'project_ids' => ['required', 'array', 'min:1', 'max:3'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return ['project_ids' => 'projects', 'project_ids.*' => 'project'];
    }

    public function checks(array $data): array
    {
        $ids = array_map(intval(...), $data['project_ids'] ?? []);
        $withUpdates = ProjectProgressUpdate::query()->whereIn('project_id', $ids)->distinct()->pluck('project_id')->all();
        $without = array_diff($ids, $withUpdates);

        return $without === [] ? [] : ['project_ids' => 'Every project needs at least one construction update.'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'stages' => BlockData::progressStages(),
            'items' => BlockData::progressSummaries(array_values(array_map(intval(...), $data['project_ids'] ?? []))),
        ];
    }
}
