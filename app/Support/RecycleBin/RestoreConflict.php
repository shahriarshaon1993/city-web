<?php

namespace App\Support\RecycleBin;

/**
 * DEL-05: one thing the user must decide before an item comes back. The bin shows a dialog:
 * `slug` asks for a new value (prefilled with `suggestion`), `parent` offers "Restore {parent} too".
 */
final readonly class RestoreConflict
{
    public function __construct(
        public string $field,
        public string $kind, // 'slug' | 'parent'
        public string $message,
        public ?string $suggestion = null,
    ) {}

    /**
     * @return array{field: string, kind: string, message: string, suggestion: string|null}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'kind' => $this->kind,
            'message' => $this->message,
            'suggestion' => $this->suggestion,
        ];
    }
}
