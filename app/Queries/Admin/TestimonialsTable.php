<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\TestimonialRowResource;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-09 testimonials: the quotes in the order the site shows them, with the Published switch.
 *
 * @extends SocialTable<Testimonial>
 */
final class TestimonialsTable extends SocialTable
{
    public const string KEY = 'testimonials';

    public function key(): string
    {
        return self::KEY;
    }

    public function url(): string
    {
        return route('admin.testimonials.index');
    }

    protected function model(): string
    {
        return Testimonial::class;
    }

    protected function baseQuery(User $actor): Builder
    {
        return Testimonial::query()->with(['project:id,name', 'photo.media']);
    }

    protected function filters(): array
    {
        return [
            $this->statusFilter(),
            AllowedFilter::callback('nrb', function (Builder $query, mixed $value): void {
                $query->where('is_nrb', in_array('yes', (array) $value, true));
            }),
        ];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'person_name', 'is_published', 'created_at'];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = $this->likeTerm($term);

        $query->where(fn (Builder $query) => $query->where('quote', 'like', $like)->orWhere('person_name', 'like', $like)->orWhere('designation', 'like', $like));
    }

    protected function resource(): string
    {
        return TestimonialRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'person_name' => ['label' => 'Name', 'value' => fn (Testimonial $testimonial): string => $testimonial->person_name],
            'designation' => ['label' => 'Designation', 'value' => fn (Testimonial $testimonial): ?string => $testimonial->designation],
            'quote' => ['label' => 'Quote', 'value' => fn (Testimonial $testimonial): string => $testimonial->quote],
            'project' => ['label' => 'Project', 'value' => fn (Testimonial $testimonial): ?string => $testimonial->project?->name],
            'is_nrb' => ['label' => 'NRB', 'value' => fn (Testimonial $testimonial): string => $testimonial->is_nrb ? 'Yes' : 'No'],
            'is_published' => ['label' => 'Published', 'value' => fn (Testimonial $testimonial): string => $testimonial->is_published ? 'Yes' : 'No'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return [
            'status' => $this->statusOptions(),
            'nrb' => [['value' => 'yes', 'label' => 'NRB buyers only', 'count' => Testimonial::query()->where('is_nrb', true)->count()]],
        ];
    }
}
