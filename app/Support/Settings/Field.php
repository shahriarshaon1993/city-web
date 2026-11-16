<?php

namespace App\Support\Settings;

use App\Models\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * One setting in the registry (spec/cms.md §8.2): its type, default, limits, help line and the
 * validation rules that follow from them. The registry is the single source for the admin form
 * (`schema()`), the Form Request (`rules()`), what gets stored (`normalise()`) and the value
 * the site reads when nothing was saved yet (`default`). VAL-01..03, HELP-03.
 *
 * A field is a scalar, a media id, an `object` (named children) or a `list` (of one scalar kind
 * via `of()`, or of objects via `ofObject()`).
 */
final class Field
{
    public const string TEXT = 'text';

    public const string TEXTAREA = 'textarea';

    public const string URL = 'url';

    public const string LINK = 'link';

    public const string EMAIL = 'email';

    public const string PHONE = 'phone';

    public const string BOOL = 'bool';

    public const string INT = 'int';

    public const string SELECT = 'select';

    public const string MEDIA = 'media';

    public const string SECRET = 'secret';

    public const string OBJECT = 'object';

    public const string LIST = 'list';

    private const string PHONE_PATTERN = '/^\+?[0-9][0-9 \-]{4,19}$/';

    public mixed $default = null;

    public ?string $help = null;

    public ?string $placeholder = null;

    public ?int $max = null;

    public ?int $min = null;

    public bool $required = false;

    public int $rows = 3;

    public bool $mono = false;

    public bool $upper = false;

    public ?int $maxItems = null;

    public int $minItems = 0;

    /** @var list<array{value: string, label: string}> */
    public array $options = [];

    /** @var list<Field> */
    public array $children = [];

    public ?Field $item = null;

    /** @var list<string> */
    private array $hosts = [];

    /** @var array{allowed: list<string>, required: list<string>}|null */
    private ?array $tokens = null;

    /** @var list<string> */
    private array $kinds = [];

    /** @var array{min_width: int|null, min_height: int|null, square: bool, mimes: list<string>, hint: string}|null */
    private ?array $mediaRules = null;

    /** @var array{regex: string, message: string}|null */
    private ?array $pattern = null;

    /** @var list<Closure|string|ValidationRule> */
    private array $extraRules = [];

    private function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly string $label,
    ) {}

    public static function text(string $key, string $label): self
    {
        return new self($key, self::TEXT, $label);
    }

    public static function textarea(string $key, string $label): self
    {
        return new self($key, self::TEXTAREA, $label);
    }

    public static function url(string $key, string $label): self
    {
        return new self($key, self::URL, $label);
    }

    /** A root-relative path, an https URL, `tel:` or `mailto:` (menu-style link). */
    public static function link(string $key, string $label): self
    {
        return new self($key, self::LINK, $label);
    }

    public static function email(string $key, string $label): self
    {
        return new self($key, self::EMAIL, $label);
    }

    public static function phone(string $key, string $label): self
    {
        return (new self($key, self::PHONE, $label))
            ->pattern(self::PHONE_PATTERN, 'Enter a phone number such as +880 9600-000000.');
    }

    public static function bool(string $key, string $label): self
    {
        return (new self($key, self::BOOL, $label))->default(false);
    }

    public static function int(string $key, string $label): self
    {
        return new self($key, self::INT, $label);
    }

    /**
     * @param  array<string, string>  $options  value → label
     */
    public static function select(string $key, string $label, array $options): self
    {
        $field = new self($key, self::SELECT, $label);
        $field->options = array_map(
            fn (string|int $value, string $text): array => ['value' => (string) $value, 'label' => $text],
            array_keys($options),
            array_values($options),
        );

        return $field;
    }

    /**
     * A library item, stored as its id. `$kinds` are MediaKind values.
     *
     * @param  list<string>  $kinds
     */
    public static function media(string $key, string $label, array $kinds = ['image', 'svg']): self
    {
        $field = new self($key, self::MEDIA, $label);
        $field->kinds = $kinds;

        return $field;
    }

    /** Write-only value, stored encrypted; never sent back to the browser (ADM-LOG-003). */
    public static function secret(string $key, string $label): self
    {
        return new self($key, self::SECRET, $label);
    }

    /**
     * @param  list<Field>  $children
     */
    public static function object(string $key, string $label, array $children): self
    {
        $field = new self($key, self::OBJECT, $label);
        $field->children = $children;

        return $field;
    }

    /** A list of one scalar kind: `Field::list('phones', 'Phones')->of(Field::phone('phone', 'Phone'))`. */
    public static function list(string $key, string $label): self
    {
        return (new self($key, self::LIST, $label))->default([]);
    }

    public function of(Field $item): self
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @param  list<Field>  $children
     */
    public function ofObject(array $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function min(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    public function rows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    public function mono(): self
    {
        $this->mono = true;

        return $this;
    }

    /** Stored in upper case (IDs the vendors print that way). */
    public function upper(): self
    {
        $this->upper = true;

        return $this;
    }

    public function maxItems(int $maxItems): self
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function minItems(int $minItems): self
    {
        $this->minItems = $minItems;

        return $this;
    }

    /**
     * Social URLs: the host (without www./m.) must be one of these or a subdomain of them.
     *
     * @param  list<string>  $hosts
     */
    public function hosts(array $hosts): self
    {
        $this->hosts = $hosts;

        return $this;
    }

    /**
     * Templates: only `{token}`s from `$allowed` may appear; every one in `$required` must.
     *
     * @param  list<string>  $allowed
     * @param  list<string>  $required
     */
    public function tokens(array $allowed, array $required = []): self
    {
        $this->tokens = ['allowed' => $allowed, 'required' => $required];

        return $this;
    }

    /**
     * Media limits checked against the chosen asset (CMS-MED-010 field hints).
     *
     * @param  list<string>  $mimes
     */
    public function mediaLimits(string $hint, ?int $minWidth = null, ?int $minHeight = null, bool $square = false, array $mimes = []): self
    {
        $this->mediaRules = ['min_width' => $minWidth, 'min_height' => $minHeight, 'square' => $square, 'mimes' => $mimes, 'hint' => $hint];

        return $this;
    }

    public function pattern(string $regex, string $message): self
    {
        $this->pattern = ['regex' => $regex, 'message' => $message];

        return $this;
    }

    public function rule(Closure|string|ValidationRule $rule): self
    {
        $this->extraRules[] = $rule;

        return $this;
    }

    public function isSecret(): bool
    {
        return $this->type === self::SECRET;
    }

    public function isMedia(): bool
    {
        return $this->type === self::MEDIA;
    }

    // --- Value handling --------------------------------------------------------------------------

    /**
     * The value the site uses for what is stored, with the default where nothing was saved.
     *
     * @param  bool  $exists  whether a row exists (a stored null is a deliberate clear)
     */
    public function hydrate(mixed $stored, bool $exists): mixed
    {
        if ($this->type === self::OBJECT) {
            $stored = is_array($stored) ? $stored : [];
            $out = [];

            foreach ($this->children as $child) {
                $out[$child->key] = $child->hydrate($stored[$child->key] ?? null, array_key_exists($child->key, $stored));
            }

            return $out;
        }

        if (! $exists) {
            return $this->default;
        }

        if ($this->type === self::LIST) {
            return array_values(array_map(
                fn (mixed $row): mixed => $this->item !== null
                    ? $row
                    : $this->hydrateRow($row),
                is_array($stored) ? $stored : [],
            ));
        }

        return $stored;
    }

    /**
     * What the form shows: strings never null (a cleared field must not look "changed"), numbers
     * as strings, secrets always empty.
     */
    public function clientValue(mixed $value): mixed
    {
        return match ($this->type) {
            self::OBJECT => collect($this->children)->mapWithKeys(
                fn (Field $child): array => [$child->key => $child->clientValue(is_array($value) ? ($value[$child->key] ?? null) : null)],
            )->all(),
            self::LIST => array_values(array_map(
                fn (mixed $row): mixed => $this->item !== null
                    ? $this->item->clientValue($row)
                    : $this->objectItem()->clientValue($row),
                is_array($value) ? $value : [],
            )),
            self::BOOL => (bool) $value,
            self::MEDIA => $value === null ? null : (int) $value,
            self::INT => $value === null ? '' : (string) $value,
            self::SECRET => '',
            default => $value === null ? '' : (string) $value,
        };
    }

    /** A new empty row for a list. */
    public function blank(): mixed
    {
        return $this->item !== null
            ? $this->item->clientValue(null)
            : $this->objectItem()->clientValue(null);
    }

    /**
     * Clean submitted input for storage: trimmed, typed, only known keys.
     */
    public function normalise(mixed $value): mixed
    {
        return match ($this->type) {
            self::OBJECT => collect($this->children)->mapWithKeys(
                fn (Field $child): array => [$child->key => $child->normalise(is_array($value) ? ($value[$child->key] ?? null) : null)],
            )->all(),
            self::LIST => array_values(array_filter(array_map(
                fn (mixed $row): mixed => $this->item !== null
                    ? $this->item->normalise($row)
                    : $this->objectItem()->normalise($row),
                is_array($value) ? $value : [],
            ), fn (mixed $row): bool => $this->item === null || $row !== null)),
            self::BOOL => filter_var($value, FILTER_VALIDATE_BOOL),
            self::INT => $value === null || $value === '' ? null : (int) $value,
            self::MEDIA => $value === null || $value === '' ? null : (int) $value,
            default => $this->normaliseText($value),
        };
    }

    private function normaliseText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = trim(str_replace("\r\n", "\n", $value));

        if ($text === '') {
            return null;
        }

        return $this->upper ? mb_strtoupper($text) : $text;
    }

    /** @return Field the list's object rows, as one object field */
    private function objectItem(): self
    {
        return self::object('item', $this->label, $this->children);
    }

    private function hydrateRow(mixed $row): mixed
    {
        return $this->objectItem()->hydrate($row, true);
    }

    /**
     * Media ids inside a normalised value (for the DM-MUSE usage rows).
     *
     * @return list<int>
     */
    public function mediaIds(mixed $normalised): array
    {
        return $this->isMedia() && $normalised !== null ? [(int) $normalised] : [];
    }

    // --- Admin form -----------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        $schema = [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'required' => $this->required,
            'help' => $this->help,
            'placeholder' => $this->placeholder,
            'max' => $this->max ?? $this->defaultMax(),
            'min' => $this->min,
            'rows' => in_array($this->type, [self::TEXTAREA], true) ? $this->rows : null,
            'mono' => $this->mono ?: null,
            'options' => $this->options ?: null,
            'kinds' => $this->kinds ?: null,
            'media_hint' => $this->mediaRules['hint'] ?? null,
            'max_items' => $this->maxItems,
            'min_items' => $this->minItems ?: null,
            'tokens' => $this->tokens['allowed'] ?? null,
        ];

        if ($this->type === self::OBJECT || ($this->type === self::LIST && $this->item === null)) {
            $schema['fields'] = array_map(fn (Field $child): array => $child->schema(), $this->children);
        }

        if ($this->type === self::LIST) {
            $schema['item'] = $this->item?->schema();
            $schema['blank'] = $this->blank();
        }

        return array_filter($schema, fn (mixed $value): bool => $value !== null);
    }

    private function defaultMax(): ?int
    {
        return match ($this->type) {
            self::TEXT, self::EMAIL, self::PHONE => 190,
            self::TEXTAREA => 2000,
            self::URL, self::LINK => 500,
            self::SECRET => 500,
            default => null,
        };
    }

    // --- Validation -----------------------------------------------------------------------------

    /**
     * @return array<string, list<mixed>> path → rules, including every nested path
     */
    public function rules(string $path): array
    {
        if ($this->type === self::OBJECT) {
            $rules = [$path => [$this->required ? 'required' : 'nullable', 'array']];

            foreach ($this->children as $child) {
                $rules += $child->rules("{$path}.{$child->key}");
            }

            return $rules;
        }

        if ($this->type === self::LIST) {
            $own = [$this->required || $this->minItems > 0 ? 'required' : 'nullable', 'array'];

            if ($this->minItems > 0) {
                $own[] = 'min:'.$this->minItems;
            }

            if ($this->maxItems !== null) {
                $own[] = 'max:'.$this->maxItems;
            }

            $rules = [$path => $own];

            if ($this->item !== null) {
                // A blank row is dropped on save, so an empty item is not an error.
                $rules["{$path}.*"] = $this->item->scalarRules();
            } else {
                $rules["{$path}.*"] = ['array'];

                foreach ($this->children as $child) {
                    $rules += $child->rules("{$path}.*.{$child->key}");
                }
            }

            return $rules;
        }

        return [$path => $this->scalarRules()];
    }

    /**
     * @return list<mixed>
     */
    private function scalarRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable'];

        $rules = [...$rules, ...match ($this->type) {
            self::BOOL => ['boolean'],
            self::INT => ['integer', ...($this->min !== null ? ["min:{$this->min}"] : []), ...($this->max !== null ? ["max:{$this->max}"] : [])],
            self::SELECT => ['string', Rule::in(array_column($this->options, 'value'))],
            self::MEDIA => ['integer', $this->mediaRule(...)],
            self::EMAIL => ['string', 'email:rfc', 'max:'.($this->max ?? 190)],
            self::URL => ['string', 'url:https', 'max:'.($this->max ?? 500)],
            self::LINK => ['string', 'max:'.($this->max ?? 500), $this->linkRule(...)],
            default => ['string', 'max:'.($this->max ?? $this->defaultMax() ?? 190)],
        }];

        if ($this->hosts !== []) {
            $rules[] = $this->hostRule(...);
        }

        if ($this->pattern !== null) {
            $rules[] = $this->patternRule(...);
        }

        if ($this->tokens !== null) {
            $rules[] = $this->tokenRule(...);
        }

        return [...$rules, ...$this->extraRules];
    }

    private function linkRule(string $attribute, mixed $value, Closure $fail): void
    {
        $text = (string) $value;
        $internal = str_starts_with($text, '/') && ! str_starts_with($text, '//');

        if (! $internal && preg_match('#^(https://\S+|tel:\+?[0-9 \-]+|mailto:\S+@\S+)$#', $text) !== 1) {
            $fail('Enter a page on this site such as /contact, or a full https:// link.');
        }
    }

    private function hostRule(string $attribute, mixed $value, Closure $fail): void
    {
        $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
        $host = (string) preg_replace('/^(www|m)\./', '', $host);

        foreach ($this->hosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return;
            }
        }

        $fail('Enter a link on '.implode(' or ', $this->hosts).'.');
    }

    private function patternRule(string $attribute, mixed $value, Closure $fail): void
    {
        $text = $this->upper ? mb_strtoupper((string) $value) : (string) $value;

        if ($this->pattern !== null && preg_match($this->pattern['regex'], $text) !== 1) {
            $fail($this->pattern['message']);
        }
    }

    private function tokenRule(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->tokens === null) {
            return;
        }

        preg_match_all('/\{(\w+)\}/', (string) $value, $found);
        $unknown = array_values(array_unique(array_diff($found[1], $this->tokens['allowed'])));

        if ($unknown !== []) {
            $usable = $this->tokens['allowed'] === [] ? 'none' : implode(', ', array_map(fn (string $token): string => '{'.$token.'}', $this->tokens['allowed']));
            $fail('{'.$unknown[0].'} is not available here. You can use: '.$usable.'.');

            return;
        }

        foreach ($this->tokens['required'] as $token) {
            if (! in_array($token, $found[1], true)) {
                $fail('Keep {'.$token.'} in the text; it is replaced by the page name.');

                return;
            }
        }
    }

    private function mediaRule(string $attribute, mixed $value, Closure $fail): void
    {
        $asset = MediaAsset::query()->with('media')->find((int) $value);

        if ($asset === null) {
            $fail('The chosen file no longer exists. Choose another one.');

            return;
        }

        if ($this->kinds !== [] && ! in_array($asset->kind->value, $this->kinds, true)) {
            $fail('Choose an image'.(in_array('svg', $this->kinds, true) ? ' (PNG, JPG, WebP or SVG)' : '').'.');

            return;
        }

        $rules = $this->mediaRules;

        if ($rules === null) {
            return;
        }

        $mime = $asset->file()?->mime_type;

        if ($rules['mimes'] !== [] && ! in_array($mime, $rules['mimes'], true)) {
            $fail('Choose a file of this type: '.$rules['hint'].'.');

            return;
        }

        if (($rules['min_width'] !== null && (int) $asset->width < $rules['min_width']) || ($rules['min_height'] !== null && (int) $asset->height < $rules['min_height'])) {
            $fail('This image is '.$asset->width.' × '.$asset->height.' px. Choose one that is at least '.$rules['min_width'].' × '.$rules['min_height'].' px.');

            return;
        }

        if ($rules['square'] && (int) $asset->width !== (int) $asset->height) {
            $fail('Choose a square image; this one is '.$asset->width.' × '.$asset->height.' px.');
        }
    }
}
