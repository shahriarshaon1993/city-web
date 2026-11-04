<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use App\Support\Activity\ActivityAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * One row of the activity log (ADM-SCR-17): who, what, the old → new changes for the
 * expandable diff, and the remaining properties (ADM-LOG-001: rich text cut at 500 characters).
 *
 * @mixin Activity
 */
class ActivityRowResource extends JsonResource
{
    private const int MAX_VALUE_LENGTH = 500;

    /** Shown in their own columns, not under "Details". */
    private const array CONTEXT_KEYS = ['ip', 'user_agent', 'old', 'attributes'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties->toArray();
        $causer = $this->causer;
        $subject = $this->subject;

        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'who' => $causer instanceof User
                ? ['name' => $causer->name, 'role' => $causer->getRoleNames()->first(), 'deleted' => $causer->trashed()]
                : null,
            'action' => ActivityAction::for($this->resource),
            'log' => $this->log_name,
            'description' => $this->description,
            'subject' => $this->subject_type === null ? null : [
                'type' => Str::headline((string) $this->subject_type),
                'label' => (string) (data_get($subject, 'name') ?? data_get($subject, 'title') ?? '#'.$this->subject_id),
                'missing' => $subject === null,
            ],
            'ip' => $properties['ip'] ?? null,
            'user_agent' => $properties['user_agent'] ?? null,
            'changes' => $this->changes($properties),
            'details' => $this->details($properties),
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return list<array{field: string, old: string|null, new: string|null}>
     */
    private function changes(array $properties): array
    {
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];
        $new = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];

        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            $changes[] = [
                'field' => Str::headline((string) $field),
                'old' => array_key_exists($field, $old) ? $this->display($old[$field]) : null,
                'new' => array_key_exists($field, $new) ? $this->display($new[$field]) : null,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return list<array{key: string, value: string}>
     */
    private function details(array $properties): array
    {
        $details = [];

        foreach (array_diff_key($properties, array_flip(self::CONTEXT_KEYS)) as $key => $value) {
            $details[] = ['key' => Str::headline((string) $key), 'value' => (string) $this->display($value)];
        }

        return $details;
    }

    private function display(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return Str::limit($text, self::MAX_VALUE_LENGTH);
    }
}
