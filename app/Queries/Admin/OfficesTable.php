<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\OfficeRowResource;
use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-09 offices: head office first on the site; here in drag order. There is no Published
 * switch (matrix): an office is shown wherever offices are listed, and deleted when it closes.
 *
 * @extends SocialTable<Office>
 */
final class OfficesTable extends SocialTable
{
    public const string KEY = 'offices';

    public function key(): string
    {
        return self::KEY;
    }

    public function url(): string
    {
        return route('admin.offices.index');
    }

    protected function model(): string
    {
        return Office::class;
    }

    protected function filters(): array
    {
        return [];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'name', 'city'];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = $this->likeTerm($term);

        $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('address', 'like', $like)->orWhere('city', 'like', $like));
    }

    protected function resource(): string
    {
        return OfficeRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Office', 'value' => fn (Office $office): string => $office->name],
            'address' => ['label' => 'Address', 'value' => fn (Office $office): string => $office->address],
            'city' => ['label' => 'City', 'value' => fn (Office $office): string => $office->city],
            'phones' => ['label' => 'Phones', 'value' => fn (Office $office): string => implode(', ', $office->phones), 'text' => true],
            'email' => ['label' => 'Email', 'value' => fn (Office $office): ?string => $office->email],
            'is_head_office' => ['label' => 'Head office', 'value' => fn (Office $office): string => $office->is_head_office ? 'Yes' : 'No'],
        ];
    }
}
