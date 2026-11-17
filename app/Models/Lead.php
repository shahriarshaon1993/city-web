<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-LEAD: one enquiry from a public form. Created and touched only by the Actions in
 * `app/Actions/Leads`; the timeline lives in `lead_activities` (DM-LEADH). The admin screens
 * come with 15.2.
 *
 * @property int $id
 * @property string $reference
 * @property string $type
 * @property LeadStatus $status
 * @property string $name
 * @property string $phone
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $country
 * @property int|null $project_id
 * @property int|null $unit_type_id
 * @property int|null $assigned_to
 * @property array<string, mixed>|null $answers
 * @property string|null $message
 * @property list<array{path: string, name: string, size: int, mime: string}>|null $attachments
 * @property string $source_url
 * @property string|null $form_location
 * @property string $consent_text
 * @property string|null $ip
 * @property string|null $ip_hash
 * @property int $touches_count
 * @property Carbon|null $last_touched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $deleted_at
 */
#[Guarded([])]
class Lead extends Model
{
    use SoftDeletes;

    /** FR-LEAD-004: a repeat enquiry inside this window is a touch, not a new lead. */
    public const int DUPLICATE_WINDOW_HOURS = 24;

    /** NFR-PRIV-04: raw IPs are replaced by a hash after this many days. */
    public const int IP_RETENTION_DAYS = 90;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'answers' => 'array',
            'attachments' => 'array',
            'follow_up_at' => 'datetime',
            'last_touched_at' => 'datetime',
            'touches_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<ProjectUnitType, $this>
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(ProjectUnitType::class, 'unit_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at')->orderBy('id');
    }
}
