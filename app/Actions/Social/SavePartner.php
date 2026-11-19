<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** FR-SOC-002: creates or edits a partner (the dialog). A new one goes to the end of the marquee. */
final class SavePartner
{
    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly SyncMediaUsages $syncMediaUsages,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated `name`, `logo_asset_id`, `url`, `category`, `is_published`
     * @return array{partner: Partner, changed: bool}
     */
    public function handle(User $actor, array $data, ?Partner $partner = null): array
    {
        return DB::transaction(function () use ($actor, $data, $partner): array {
            $creating = $partner === null;
            $partner ??= new Partner(['sort_order' => ((int) Partner::query()->max('sort_order')) + 1]);

            $partner->fill([
                'name' => trim((string) $data['name']),
                'logo_asset_id' => $data['logo_asset_id'],
                'url' => filled($data['url'] ?? null) ? trim((string) $data['url']) : null,
                'category' => $data['category'],
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);

            $changed = $creating || $partner->isDirty();

            if ($changed) {
                $partner->save();

                $this->syncMediaUsages->handle($partner, 'logo', $partner->logo_asset_id === null ? [] : [$partner->logo_asset_id]);

                $this->logActivity->handle(
                    'partners',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Partner created: ' : 'Partner updated: ').$partner->name,
                    $actor,
                    $partner,
                    ['label' => $partner->name],
                );
            }

            return ['partner' => $partner, 'changed' => $changed];
        });
    }
}
