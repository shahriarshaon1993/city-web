<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-SOC-001: creates or edits a testimonial. A new one goes to the end of the order and starts
 * unpublished unless the editor switched it on (CRUD-03).
 */
final class SaveTestimonial
{
    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly SyncMediaUsages $syncMediaUsages,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated form values
     * @return array{testimonial: Testimonial, changed: bool}
     */
    public function handle(User $actor, array $data, ?Testimonial $testimonial = null): array
    {
        return DB::transaction(function () use ($actor, $data, $testimonial): array {
            $creating = $testimonial === null;
            $testimonial ??= new Testimonial(['sort_order' => ((int) Testimonial::query()->max('sort_order')) + 1]);

            $testimonial->fill([
                'quote' => trim((string) $data['quote']),
                'person_name' => trim((string) $data['person_name']),
                'designation' => filled($data['designation'] ?? null) ? trim((string) $data['designation']) : null,
                'project_id' => $data['project_id'] ?? null,
                'photo_asset_id' => $data['photo_asset_id'] ?? null,
                'is_nrb' => (bool) ($data['is_nrb'] ?? false),
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);

            $changed = $creating || $testimonial->isDirty();

            if ($changed) {
                $testimonial->save();

                $this->syncMediaUsages->handle($testimonial, 'photo', $testimonial->photo_asset_id === null ? [] : [$testimonial->photo_asset_id]);

                $this->logActivity->handle(
                    'testimonials',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Testimonial created: ' : 'Testimonial updated: ').$testimonial->person_name,
                    $actor,
                    $testimonial,
                    ['label' => $testimonial->person_name],
                );
            }

            return ['testimonial' => $testimonial, 'changed' => $changed];
        });
    }
}
