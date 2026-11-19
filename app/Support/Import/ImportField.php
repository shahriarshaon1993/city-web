<?php

namespace App\Support\Import;

/**
 * One column an import understands: the key the definition reads, the label the mapping step shows,
 * the header names it is matched to automatically (ADM-IO-001) and an example for the template file.
 */
final readonly class ImportField
{
    /**
     * @param  list<string>  $aliases  other header names that mean this field
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $required = false,
        public array $aliases = [],
        public string $example = '',
        public ?string $hint = null,
    ) {}

    /** The form a header or alias is compared in: lower case, words separated by single spaces, no `p:` prefix. */
    public static function normalise(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[\s_\-.]+/', ' ', $header) ?? $header;

        return trim($header);
    }

    public function matches(string $header): bool
    {
        $needle = self::normalise($header);

        if ($needle === '') {
            return false;
        }

        foreach ([$this->key, $this->label, ...$this->aliases] as $name) {
            if (self::normalise($name) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{key: string, label: string, required: bool, hint: string|null}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'required' => $this->required, 'hint' => $this->hint];
    }
}
