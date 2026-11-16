<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Read side of Settings (DM-SET). All rows load in one cached query, `settings:v{n}`, where `n` is
 * the `cachever:settings` counter that `UpdateSettings` raises on every save (CMS-CACHE-002). A
 * key nobody saved yet falls back to the registry default, so a fresh install already has a
 * complete site. Secrets are decrypted only by `secret()` and never appear in `group()`.
 */
final class Settings
{
    private const string VERSION_KEY = 'cachever:settings';

    /** @var array<string, array<string, array{value: mixed, secret: bool}>>|null */
    private static ?array $rows = null;

    /** @var array<string, array<string, mixed>> */
    private static array $groups = [];

    /**
     * Every field of a group, stored value or default, secrets left out.
     *
     * @return array<string, mixed>
     */
    public static function group(string $group): array
    {
        if (isset(self::$groups[$group])) {
            return self::$groups[$group];
        }

        $definition = SettingsRegistry::find($group);

        if ($definition === null) {
            return [];
        }

        $stored = self::rows()[$group] ?? [];
        $values = [];

        foreach ($definition->fields() as $field) {
            if ($field->isSecret()) {
                continue;
            }

            $values[$field->key] = $field->hydrate($stored[$field->key]['value'] ?? null, array_key_exists($field->key, $stored));
        }

        return self::$groups[$group] = $values;
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        return self::group($group)[$key] ?? $default;
    }

    /**
     * A secret, decrypted; null when none is set. Server-side use only (Turnstile check, webhook signing).
     */
    public static function secret(string $group, string $key): ?string
    {
        $cipher = self::rows()[$group][$key]['value'] ?? null;

        if (! is_string($cipher) || $cipher === '') {
            return null;
        }

        try {
            return Crypt::decryptString($cipher);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function hasSecret(string $group, string $key): bool
    {
        $cipher = self::rows()[$group][$key]['value'] ?? null;

        return is_string($cipher) && $cipher !== '';
    }

    /**
     * The newest save in a group, for "Last saved".
     */
    public static function lastSaved(string $group): ?string
    {
        $latest = Setting::query()->where('group', $group)->max('updated_at');

        return $latest === null ? null : (string) $latest;
    }

    /**
     * Called by `UpdateSettings` after a save: every key built on the old version is stale.
     */
    public static function bump(): void
    {
        $current = self::version();

        Cache::forever(self::VERSION_KEY, $current + 1);
        Cache::forget("settings:v{$current}");

        self::flushMemo();
    }

    /** Drops what this request has already read (after a save, in checks). */
    public static function flushMemo(): void
    {
        self::$rows = null;
        self::$groups = [];
        SharedSettings::flushMemo();
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * @return array<string, array<string, array{value: mixed, secret: bool}>>
     */
    private static function rows(): array
    {
        return self::$rows ??= Cache::rememberForever('settings:v'.self::version(), function (): array {
            $rows = [];

            foreach (Setting::query()->get(['group', 'key', 'value', 'is_secret']) as $setting) {
                $rows[$setting->group][$setting->key] = ['value' => $setting->value, 'secret' => $setting->is_secret];
            }

            return $rows;
        });
    }
}
