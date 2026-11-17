<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DM-WEBH: one outbound webhook call, updated on every attempt and pruned after 30 days.
 *
 * @property int $id
 * @property string $event
 * @property int|null $lead_id
 * @property int|null $status_code
 * @property int $attempts
 * @property string|null $response_excerpt
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 */
#[Guarded([])]
class WebhookDelivery extends Model
{
    public const UPDATED_AT = null;

    /** FR-LEAD-012: how long the delivery log is kept. */
    public const int RETENTION_DAYS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'attempts' => 'integer', 'status_code' => 'integer'];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }
}
