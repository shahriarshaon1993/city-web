<?php

namespace App\Models;

use App\Actions\Leads\StoreLeadDocuments;
use App\Enums\LeadStatus;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * DM-LEAD: one enquiry from a public form, or one that staff added by hand (type `manual`, DEC-33).
 * Created and touched only by the Actions in `app/Actions/Leads`; the timeline lives in
 * `lead_activities` (DM-LEADH). Delete = Recycle bin for 365 days, Admin only (crud.md Leads).
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
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property string|null $referrer_host
 * @property string $consent_text
 * @property string|null $ip
 * @property string|null $ip_hash
 * @property Carbon|null $follow_up_at
 * @property string|null $lost_reason
 * @property string|null $won_note
 * @property int $touches_count
 * @property Carbon|null $last_touched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $deleted_at
 */
#[Guarded([])]
class Lead extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    /** FR-LEAD-004: a repeat enquiry inside this window is a touch, not a new lead. */
    public const int DUPLICATE_WINDOW_HOURS = 24;

    /** NFR-PRIV-04: raw IPs are replaced by a hash after this many days. */
    public const int IP_RETENTION_DAYS = 90;

    /** The type of a lead that staff add by hand. */
    public const string MANUAL_TYPE = 'manual';

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
     * AC-LEAD-05: `leads.view-all` sees every lead, `leads.view-assigned` only the user's own.
     *
     * @param  Builder<Lead>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->can('leads.view-all')) {
            return;
        }

        if ($user->can('leads.view-assigned')) {
            $query->where('assigned_to', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
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

    /** FR-LEAD-009: a follow-up that is due counts as overdue until it is cleared or moved. */
    public function followUpIsOverdue(): bool
    {
        return $this->follow_up_at !== null
            && $this->follow_up_at->isPast()
            && ! in_array($this->status->value, LeadStatus::closedValues(), true);
    }

    public static function binType(): string
    {
        return 'lead';
    }

    public static function binTypeLabel(): string
    {
        return 'Lead';
    }

    public static function binPermission(): string
    {
        return 'leads.delete';
    }

    /** DEL-06: leads stay 365 days. */
    public static function binRetentionDays(): ?int
    {
        return 365;
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.leads.index', ['lead' => $this->id]);
    }

    /** DEL-07: the landowner documents go with the row, never before. */
    public function binForceDeleting(): void
    {
        Storage::disk(StoreLeadDocuments::DISK)->deleteDirectory("leads/{$this->reference}");
    }
}
