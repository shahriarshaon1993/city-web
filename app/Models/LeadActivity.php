<?php

namespace App\Models;

use App\Enums\LeadActivityKind;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DM-LEADH: one line of a lead's timeline (submission, touch, status change, assignment, note…).
 * Append-only: there is no `updated_at`.
 *
 * @property int $id
 * @property int $lead_id
 * @property int|null $user_id
 * @property LeadActivityKind $kind
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 */
#[Guarded([])]
class LeadActivity extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => LeadActivityKind::class, 'payload' => 'array'];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
