<?php

namespace App\Models;

use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use App\Support\Redirects\RuleSet;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-REDIR (SEO-TEC-008): one rule of the redirect manager. A prefix rule matches its address and
 * everything below it; `source` never carries the `/*`. Delete = Recycle bin (crud.md Redirects).
 * Any change to the stored rules raises the cache version the `ApplyRedirects` middleware reads.
 *
 * @property int $id
 * @property string $source
 * @property bool $is_prefix
 * @property string|null $target
 * @property int $code
 * @property bool $is_active
 * @property int $hits
 * @property Carbon|null $last_hit_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['source', 'is_prefix', 'target', 'code', 'is_active', 'created_by'])]
class Redirect extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    /** SEO-TEC-008: the most rules the manager holds (the bin does not count). */
    public const int MAX_RULES = 2000;

    /** @var list<int> */
    public const array CODES = [301, 302, 410];

    protected static function booted(): void
    {
        $bump = static fn () => RuleSet::bump();

        static::saved($bump);
        static::deleted($bump);
        static::restored($bump);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_prefix' => 'boolean',
            'is_active' => 'boolean',
            'code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The source as people type and read it: `/old-blog/*` for a prefix rule. */
    public function sourceLabel(): string
    {
        return $this->is_prefix ? rtrim($this->source, '/').'/*' : $this->source;
    }

    public static function binType(): string
    {
        return 'redirect';
    }

    public static function binTypeLabel(): string
    {
        return 'Redirect';
    }

    public static function binPermission(): string
    {
        return 'redirects.manage';
    }

    public function binLabel(): string
    {
        return $this->sourceLabel();
    }

    public function binUrl(): ?string
    {
        return route('admin.redirects.index', ['search' => $this->source]);
    }

    /**
     * DEL-05: a restored rule must not close a circle with rules created since; if it would, it
     * comes back switched off rather than breaking the site.
     */
    public function binResolveConflicts(array $resolution): void
    {
        if ($this->is_active && RuleSet::fromDatabase()->withRule($this)->loops($this->source)) {
            $this->is_active = false;
        }
    }
}
