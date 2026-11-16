<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Support\Settings\Field;
use App\Support\Settings\SettingsGroup;
use App\Support\Settings\SettingsRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * One Settings tab. The rules come from the registry (VAL-01): the limits the form shows
 * beforehand are the ones enforced here.
 */
class UpdateSettingsRequest extends FormRequest
{
    public function group(): SettingsGroup
    {
        $group = SettingsRegistry::find((string) $this->route('group'));

        abort_if($group === null, 404);

        return $group;
    }

    public function authorize(): bool
    {
        return Gate::allows('updateGroup', [Setting::class, $this->group()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'values' => ['required', 'array'],
            'clear' => ['nullable', 'array'],
            'clear.*' => ['string', 'in:'.implode(',', $this->secretKeys())],
        ];

        foreach ($this->group()->fields() as $field) {
            $rules += $field->rules("values.{$field->key}");
        }

        return $rules;
    }

    /**
     * VAL-02: errors name the field as the form does.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->group()->fields() as $field) {
            $attributes += self::attributeNames($field, "values.{$field->key}", '');
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    private static function attributeNames(Field $field, string $path, string $prefix): array
    {
        $label = mb_strtolower($prefix === '' ? $field->label : "{$prefix} {$field->label}");
        $names = [$path => $label];

        if ($field->type === Field::OBJECT) {
            foreach ($field->children as $child) {
                $names += self::attributeNames($child, "{$path}.{$child->key}", $field->label);
            }
        }

        if ($field->type === Field::LIST) {
            $names["{$path}.*"] = mb_strtolower($field->item->label ?? $field->label);

            foreach ($field->children as $child) {
                $names += self::attributeNames($child, "{$path}.*.{$child->key}", $field->label);
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function secretKeys(): array
    {
        $keys = [];

        foreach ($this->group()->fields() as $field) {
            if ($field->isSecret()) {
                $keys[] = $field->key;
            }
        }

        return $keys === [] ? ['-'] : $keys;
    }
}
