<?php

namespace App\Models;

use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use App\Support\Users\UserGuard;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $phone
 * @property int|null $avatar_asset_id
 * @property string $password
 * @property bool $is_active
 * @property string $theme
 * @property string $table_density
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, Trashable, UsesMedia
{
    /** @use HasFactory<UserFactory> */
    use BinBehaviour, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public static function binType(): string
    {
        return 'user';
    }

    public static function binTypeLabel(): string
    {
        return 'User';
    }

    public static function binPermission(): string
    {
        return 'users.delete';
    }

    /** DEL-06: users are never auto-deleted, the audit trail points at them. */
    public static function binRetentionDays(): ?int
    {
        return null;
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.users.index', ['search' => $this->email]);
    }

    public function binUsageBlock(self $actor): ?string
    {
        return UserGuard::deleteBlock($actor, $this);
    }

    public function binTrashed(): void
    {
        DB::table('sessions')->where('user_id', $this->getKey())->delete();
    }

    /**
     * FR-LEAD-007: the leads assigned to this user.
     *
     * @return HasMany<Lead, $this>
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /**
     * ADM-SCR-20 profile picture (DM-USER `avatar_asset_id`, plus its DM-MUSE row `avatar`).
     *
     * @return BelongsTo<MediaAsset, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'avatar_asset_id');
    }

    public function mediaUsageLabel(string $field): string
    {
        return "Profile picture · {$this->name}";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.users.index', ['search' => $this->email]);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($field === 'avatar' && $this->avatar_asset_id === $fromAssetId) {
            $this->forceFill(['avatar_asset_id' => $toAssetId])->saveQuietly();
        }
    }
}
