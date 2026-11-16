<?php

namespace App\Actions\Settings;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsGroup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Saves one Settings tab (spec/cms.md §8.2). Only keys that actually changed are written, media
 * fields are recorded as DM-MUSE usages, the versioned cache is raised afterwards (CMS-CACHE-002)
 * and one activity entry lists the key-level differences; a secret is logged as "changed"
 * without its value (ADM-LOG-003).
 */
final class UpdateSettings
{
    private const int LOG_VALUE_LIMIT = 200;

    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly SyncMediaUsages $syncMediaUsages,
    ) {}

    /**
     * @param  array<string, mixed>  $values  submitted values by field key
     * @param  list<string>  $clearSecrets  secret keys to remove (a blank secret otherwise keeps its value)
     * @return list<string> the labels of the fields that changed
     */
    public function handle(User $actor, SettingsGroup $group, array $values, array $clearSecrets = []): array
    {
        $changes = DB::transaction(function () use ($actor, $group, $values, $clearSecrets): array {
            $stored = Setting::query()->where('group', $group->key)->lockForUpdate()->get()->keyBy('key');
            $changes = [];

            foreach ($group->fields() as $field) {
                $existing = $stored->get($field->key);

                if ($field->isSecret()) {
                    $change = $this->secretChange($field->key, $values[$field->key] ?? null, $clearSecrets, $existing);

                    if ($change === false) {
                        continue;
                    }

                    $this->store($actor, $group->key, $field->key, $change, true);
                    $changes[$field->key] = ['label' => $field->label, 'secret' => true, 'old' => null, 'new' => null];

                    continue;
                }

                $new = $field->normalise($values[$field->key] ?? null);
                $old = $field->normalise($field->hydrate($existing?->value, $existing !== null));

                if (json_encode($new) === json_encode($old)) {
                    continue;
                }

                $row = $this->store($actor, $group->key, $field->key, $new, false);

                if ($field->isMedia()) {
                    $this->syncMediaUsages->handle($row, 'value', $field->mediaIds($new));
                }

                $changes[$field->key] = ['label' => $field->label, 'secret' => false, 'old' => $this->forLog($old), 'new' => $this->forLog($new)];
            }

            if ($changes !== []) {
                $this->logActivity->handle('settings', 'updated', "Settings changed: {$group->label}", $actor, null, [
                    'group' => $group->key,
                    'fields' => array_keys($changes),
                    'changes' => array_map(
                        fn (array $change): array|string => $change['secret'] ? 'changed' : ['old' => $change['old'], 'new' => $change['new']],
                        $changes,
                    ),
                ]);
            }

            return $changes;
        });

        if ($changes !== []) {
            Settings::bump();
        }

        return array_values(array_map(fn (array $change): string => $change['label'], $changes));
    }

    /**
     * @param  list<string>  $clearSecrets
     * @return string|null|false the new encrypted value, null to clear, false when nothing changes
     */
    private function secretChange(string $key, mixed $input, array $clearSecrets, ?Setting $existing): string|false|null
    {
        $hasValue = $existing !== null && is_string($existing->value) && $existing->value !== '';

        if (is_string($input) && trim($input) !== '') {
            return Crypt::encryptString(trim($input));
        }

        if (in_array($key, $clearSecrets, true) && $hasValue) {
            return null;
        }

        return false;
    }

    private function store(User $actor, string $group, string $key, mixed $value, bool $secret): Setting
    {
        $row = Setting::query()->firstOrNew(['group' => $group, 'key' => $key]);
        $row->fill(['value' => $value, 'is_secret' => $secret, 'updated_by' => $actor->id])->save();

        return $row;
    }

    /**
     * Long text (scripts, consent wording) is cut so one entry stays small.
     */
    private function forLog(mixed $value): mixed
    {
        if (is_string($value) && mb_strlen($value) > self::LOG_VALUE_LIMIT) {
            return mb_substr($value, 0, self::LOG_VALUE_LIMIT).'…';
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';

            return mb_strlen($encoded) > self::LOG_VALUE_LIMIT ? mb_substr($encoded, 0, self::LOG_VALUE_LIMIT).'…' : $value;
        }

        return $value;
    }
}
