<?php

namespace App\Models;

use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DM-OFF / FR-SOC-005: an office (PG-17, the footer and LocalBusiness JSON-LD). Delete = Recycle bin,
 * blocked while a job post is based there (DEL-03; the `job_posts` table arrives with 17.1).
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $city
 * @property string|null $postal_code
 * @property list<string> $phones
 * @property string|null $email
 * @property list<array{days: list<string>, opens: string, closes: string}> $hours
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $is_head_office
 * @property int $sort_order
 */
#[Fillable(['name', 'address', 'city', 'postal_code', 'phones', 'email', 'hours', 'latitude', 'longitude', 'is_head_office', 'sort_order'])]
class Office extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    public const int NAME_MAX = 100;

    /** FR-SOC-005 opening-hours days, in the order the week reads in Bangladesh. */
    public const array DAYS = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'hours' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_head_office' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Job posts based at this office (0 until the careers module exists). */
    public function jobPostCount(): int
    {
        return Schema::hasTable('job_posts') ? DB::table('job_posts')->where('office_id', $this->id)->whereNull('deleted_at')->count() : 0;
    }

    public static function binType(): string
    {
        return 'office';
    }

    public static function binTypeLabel(): string
    {
        return 'Office';
    }

    public static function binPermission(): string
    {
        return 'social.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.offices.edit', $this);
    }

    public function binUsageBlock(User $actor): ?string
    {
        $count = $this->jobPostCount();

        return $count === 0 ? null : "“{$this->name}” has {$count} job ".($count === 1 ? 'post' : 'posts').'. Move them to another office first.';
    }
}
