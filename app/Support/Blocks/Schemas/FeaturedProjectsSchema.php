<?php

namespace App\Support\Blocks\Schemas;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Queries\Site\ProjectCards;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Search\ProjectFilters;
use Illuminate\Validation\Rule;

/**
 * Block 4, CMP-BLK-FEATURED: source `featured`, `manual` (≥ 1 project, ≤ 12), `segment` or `status`,
 * limit 3–12, layout carousel or grid. Prices on the cards follow AC-PROJ-01.
 */
final class FeaturedProjectsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'source' => BlockRules::choice(['featured', 'manual', 'segment', 'status']),
            'manual_ids' => ['required_if:source,manual', 'nullable', 'array', 'max:12'],
            'manual_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'segment' => ['required_if:source,segment', 'nullable', 'string', Rule::enum(Segment::class)],
            'status' => ['required_if:source,status', 'nullable', 'string', Rule::enum(Lifecycle::class)],
            'limit' => ['required', 'integer', 'between:3,12'],
            'layout' => BlockRules::choice(['carousel', 'grid']),
        ];
    }

    public function attributes(): array
    {
        return ['manual_ids' => 'projects', 'manual_ids.*' => 'project', 'segment' => 'project type', 'status' => 'project status'];
    }

    public function messages(): array
    {
        return ['manual_ids.required_if' => 'Pick at least one project.'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $source = $data['source'] ?? 'featured';
        $limit = (int) ($data['limit'] ?? 6);
        $segment = Segment::tryFrom((string) ($data['segment'] ?? ''));
        $status = Lifecycle::tryFrom((string) ($data['status'] ?? ''));

        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'source' => $source,
            'layout' => $data['layout'] ?? 'carousel',
            'projects' => match ($source) {
                'manual' => array_slice(ProjectCards::byIds(array_values(array_map(intval(...), $data['manual_ids'] ?? []))), 0, $limit),
                'segment' => ProjectCards::filtered($segment, null, $limit),
                'status' => ProjectCards::filtered(null, $status, $limit),
                default => ProjectCards::featured($limit),
            },
            'view_all' => match (true) {
                $source === 'segment' && $segment !== null => ['label' => "View all {$segment->label()} projects", 'url' => (string) ProjectFilters::landingUrl('segment', $segment->value), 'new_tab' => false],
                $source === 'status' && $status !== null => ['label' => 'View all '.mb_strtolower(str_replace(' ', '-', $status->label())).' projects', 'url' => (string) ProjectFilters::landingUrl('status', $status->value), 'new_tab' => false],
                $source === 'manual' => null,
                default => ['label' => 'View all projects', 'url' => '/projects', 'new_tab' => false],
            },
        ];
    }
}
