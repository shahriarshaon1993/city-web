<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Models\Office;
use App\Models\User;
use App\Support\Social\OfficePhones;
use Illuminate\Support\Facades\DB;

/**
 * FR-SOC-005: creates or edits an office. There is one head office: marking this one takes the mark
 * off the others. A new office goes to the end of the order.
 */
final class SaveOffice
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated form values
     * @return array{office: Office, changed: bool}
     */
    public function handle(User $actor, array $data, ?Office $office = null): array
    {
        return DB::transaction(function () use ($actor, $data, $office): array {
            $creating = $office === null;
            $office ??= new Office(['sort_order' => ((int) Office::query()->max('sort_order')) + 1]);

            $office->fill([
                'name' => trim((string) $data['name']),
                'address' => trim((string) $data['address']),
                'city' => trim((string) $data['city']),
                'postal_code' => filled($data['postal_code'] ?? null) ? trim((string) $data['postal_code']) : null,
                'phones' => array_values(array_unique(array_filter(array_map(fn (mixed $phone): ?string => OfficePhones::normalize((string) $phone), $data['phones'] ?? [])))),
                'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
                'hours' => array_values(array_map(fn (array $row): array => [
                    'days' => array_values(array_intersect(Office::DAYS, $row['days'])),
                    'opens' => $row['opens'],
                    'closes' => $row['closes'],
                ], $data['hours'] ?? [])),
                'latitude' => filled($data['latitude'] ?? null) ? round((float) $data['latitude'], 7) : null,
                'longitude' => filled($data['longitude'] ?? null) ? round((float) $data['longitude'], 7) : null,
                'is_head_office' => (bool) ($data['is_head_office'] ?? false),
            ]);

            $changed = $creating || $office->isDirty();

            if ($changed) {
                $office->save();

                if ($office->is_head_office) {
                    Office::query()->whereKeyNot($office->id)->where('is_head_office', true)->update(['is_head_office' => false]);
                }

                $this->logActivity->handle(
                    'offices',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Office created: ' : 'Office updated: ').$office->name,
                    $actor,
                    $office,
                    ['label' => $office->name],
                );
            }

            return ['office' => $office, 'changed' => $changed];
        });
    }
}
