<?php

namespace App\Support\Blocks\Schemas;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Models\Location;
use App\Queries\Site\ProjectCards;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Search\ProjectFilters;
use Illuminate\Validation\Rule;

/** Block 5, CMP-BLK-GRID: preset filters (type / status / area), limit 3–24, optional "View all" link. */
final class ProjectGridSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'segment' => ['nullable', 'string', Rule::enum(Segment::class)],
            'status' => ['nullable', 'string', Rule::enum(Lifecycle::class)],
            'area_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')->where('type', 'area')],
            'limit' => ['required', 'integer', 'between:3,24'],
            'show_view_all' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['segment' => 'project type', 'status' => 'project status', 'area_id' => 'area'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $segment = Segment::tryFrom((string) ($data['segment'] ?? ''));
        $status = Lifecycle::tryFrom((string) ($data['status'] ?? ''));
        $area = isset($data['area_id']) ? Location::query()->areas()->find((int) $data['area_id']) : null;

        return [
            'heading' => $data['heading'] ?? null,
            'projects' => ProjectCards::filtered($segment, $status, (int) ($data['limit'] ?? 6), $area?->id),
            'view_all' => ($data['show_view_all'] ?? true) ? $this->viewAll($segment, $status, $area) : null,
        ];
    }

    /**
     * @return array{label: string, url: string, new_tab: bool}
     */
    private function viewAll(?Segment $segment, ?Lifecycle $status, ?Location $area): array
    {
        $filters = array_filter([$segment?->value, $status?->value, $area?->slug]);
        $label = match (true) {
            $status !== null && $segment === null && $area === null => 'View all '.mb_strtolower(str_replace(' ', '-', $status->label())).' projects',
            $segment !== null && $status === null && $area === null => "View all {$segment->label()} projects",
            $area !== null && $segment === null && $status === null => "View all projects in {$area->name}",
            default => 'View all projects',
        };

        if (count($filters) === 1) {
            $url = match (true) {
                $segment !== null => ProjectFilters::landingUrl('segment', $segment->value),
                $status !== null => ProjectFilters::landingUrl('status', $status->value),
                default => ProjectFilters::landingUrl('area', (string) $area?->slug),
            };
        } else {
            $query = http_build_query(array_filter(['segment' => $segment?->value, 'status' => $status?->value, 'area' => $area === null ? null : [$area->slug]]));
            $url = '/projects'.($query === '' ? '' : '?'.$query);
        }

        return ['label' => $label, 'url' => (string) $url, 'new_tab' => false];
    }
}
