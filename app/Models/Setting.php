<?php

namespace App\Models;

use App\Support\Media\UsesMedia;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsRegistry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * DM-SET: one stored settings key. Read through `App\Support\Settings\Settings` (versioned cache,
 * CMS-CACHE-002), written only by `UpdateSettings`. A media setting (logo, favicon, OG image) is a
 * DM-MUSE usage with the field `value`.
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property mixed $value
 * @property bool $is_secret
 * @property int|null $updated_by
 */
#[Fillable(['group', 'key', 'value', 'is_secret', 'updated_by'])]
class Setting extends Model implements UsesMedia
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_secret' => 'boolean',
        ];
    }

    public function mediaUsageLabel(string $field): string
    {
        $group = SettingsRegistry::find($this->group);
        $label = $group->field($this->key)->label ?? $this->key;

        return 'Settings · '.($group->label ?? $this->group).' › '.$label;
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return url('admin/settings/'.$this->group);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ((int) $this->value === $fromAssetId) {
            $this->forceFill(['value' => $toAssetId])->saveQuietly();
            Settings::bump();
        }
    }
}
