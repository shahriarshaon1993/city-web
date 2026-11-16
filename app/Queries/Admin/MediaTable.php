<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use App\Support\Format;
use App\Support\Media\MediaKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-13 media library, grid and list view (CMS-MED-009, design/admin.md §12.3 "Media"):
 * search by file name, title, alt, caption and tags; filter by type, folder, upload date and
 * "missing alt"; sort newest / oldest / name / size; 24 / 48 / 96 per page.
 *
 * @extends AdminTable<MediaAsset>
 */
final class MediaTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'media';

    /** CMS-MED-009 */
    public const array PER_PAGE = [24, 48, 96];

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['media.view', 'media.upload', 'media.update'];
    }

    public function url(): string
    {
        return route('admin.media.index');
    }

    protected function perPageOptions(): array
    {
        return self::PER_PAGE;
    }

    protected function defaultPerPage(): int
    {
        return self::PER_PAGE[0];
    }

    protected function baseQuery(User $actor): Builder
    {
        return MediaAsset::query()
            ->select('media_assets.*')
            // `bytes` for the size sort; the file row is the item's one spatie media.
            ->addSelect(['bytes' => Media::query()
                ->select('size')
                ->whereColumn('model_id', 'media_assets.id')
                ->where('model_type', (new MediaAsset)->getMorphClass())
                ->limit(1),
            ])
            ->with(['media', 'folder:id,name', 'uploader:id,name'])
            ->withCount('usages');
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('kind', fn (Builder $query, mixed $value) => $query->whereIn('kind', Arr::wrap($value))),
            // A folder's direct contents; `unfiled` = no folder (CMS-MED-002 virtual folder).
            AllowedFilter::callback('folder', function (Builder $query, mixed $value): void {
                $value = (string) (is_array($value) ? ($value[0] ?? '') : $value);

                $value === 'unfiled'
                    ? $query->whereNull('folder_id')
                    : $query->where('folder_id', (int) $value);
            }),
            AllowedFilter::callback('missing_alt', function (Builder $query, mixed $value): void {
                $missing = fn (Builder $q) => $q->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])
                    ->where('is_decorative', false)
                    ->where(fn (Builder $alt) => $alt->whereNull('alt')->orWhere('alt', ''));

                (string) $value === '1' ? $query->where($missing) : $query->whereNot($missing);
            }),
            ...$this->dateRange('uploaded', 'media_assets.created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['created_at', 'title', 'bytes'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query
            ->where('title', 'like', $like)
            ->orWhere('alt', 'like', $like)
            ->orWhere('caption', 'like', $like)
            ->orWhere('tags', 'like', $like)
            ->orWhereHas('media', fn (Builder $media) => $media->where('custom_properties->original_name', 'like', $like)));
    }

    protected function resource(): string
    {
        return MediaAssetResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'title' => ['label' => 'File', 'value' => fn (MediaAsset $asset): string => $asset->title],
            'kind' => ['label' => 'Type', 'value' => fn (MediaAsset $asset): string => $asset->kind->label()],
            'dimensions' => ['label' => 'Dimensions', 'value' => fn (MediaAsset $asset): ?string => $asset->width && $asset->height ? "{$asset->width} × {$asset->height}" : null],
            'bytes' => ['label' => 'Size', 'value' => fn (MediaAsset $asset): ?string => $asset->getAttribute('bytes') === null ? null : Format::fileSize((int) $asset->getAttribute('bytes'))],
            'folder' => ['label' => 'Folder', 'value' => fn (MediaAsset $asset): string => $asset->folder->name ?? 'Unfiled'],
            'usages' => ['label' => 'Usages', 'value' => fn (MediaAsset $asset): int => (int) $asset->getAttribute('usages_count')],
            'alt' => ['label' => 'Alt text', 'value' => fn (MediaAsset $asset): ?string => $asset->is_decorative ? '(decorative)' : $asset->alt],
            'created_at' => ['label' => 'Uploaded', 'value' => fn (MediaAsset $asset): ?string => $asset->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $counts = MediaAsset::query()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');

        return [
            'kind' => array_map(fn (MediaKind $kind): array => [
                'value' => $kind->value,
                'label' => $kind === MediaKind::Image ? 'Image (JPEG, PNG, WebP, AVIF)' : $kind->label(),
                'count' => (int) ($counts[$kind->value] ?? 0),
            ], MediaKind::cases()),
        ];
    }
}
