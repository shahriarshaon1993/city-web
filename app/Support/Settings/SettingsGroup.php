<?php

namespace App\Support\Settings;

/**
 * One tab of Settings → a stored group of keys (DM-SET `group`). The permission decides who can
 * open and save it (spec/admin.md §10.2: `settings.manage` for Admin and up, `settings.integrations`
 * for Super Admin only).
 */
final class SettingsGroup
{
    public const string MANAGE = 'settings.manage';

    public const string INTEGRATIONS = 'settings.integrations';

    /**
     * @param  array<string, list<Field>>  $sections  section title → fields, in display order
     * @param  array<string, string>  $sectionHelp  section title → one-line description
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $nav,
        public readonly string $permission,
        public readonly array $sections,
        public readonly array $sectionHelp = [],
    ) {}

    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return array_merge(...array_values($this->sections));
    }

    public function field(string $key): ?Field
    {
        foreach ($this->fields() as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return list<array{title: string, description: string|null, fields: list<array<string, mixed>>}>
     */
    public function schema(): array
    {
        $out = [];

        foreach ($this->sections as $title => $fields) {
            $out[] = [
                'title' => $title,
                'description' => $this->sectionHelp[$title] ?? null,
                'fields' => array_map(fn (Field $field): array => $field->schema(), $fields),
            ];
        }

        return $out;
    }
}
