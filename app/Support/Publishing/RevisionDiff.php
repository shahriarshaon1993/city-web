<?php

namespace App\Support\Publishing;

use App\Models\Amenity;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * CMS-PUB-004 / CMP-ADM-REV: the field-level difference between two editor payloads. Plain fields show
 * before and after; lists show the rows that were removed and added; rich text is turned into text and
 * diffed word by word with insertions and deletions marked (`<ins>` / `<del>`, already escaped, safe for
 * `v-html`). Ids are shown as names (image titles, area names…), never bare numbers when a name exists.
 *
 * @phpstan-type Change array{key: string, label: string, kind: 'text'|'html'|'list', before: string, after: string, markup: string|null, removed: list<string>, added: list<string>}
 */
final class RevisionDiff
{
    /** Word-diff tables above this many cells fall back to "replaced as a whole". */
    private const int MAX_CELLS = 4_000_000;

    /** @var array<string, array<int, string>> */
    private array $names = ['media' => [], 'location' => [], 'amenity' => [], 'project' => [], 'user' => [], 'post_category' => []];

    public function __construct(private readonly PublishingDriver $driver) {}

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<Change>
     */
    public function between(array $before, array $after): array
    {
        $this->loadNames($before, $after);

        $labels = $this->driver->fieldLabels();
        $changes = [];

        foreach ($this->flatten($before, $after) as $key => [$old, $new]) {
            $change = $this->change($key, $old, $new, $labels);

            if ($change !== null) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * Top-level fields, with keyed sub-arrays (`seo`, `segment_intros`) opened into one entry each.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function flatten(array $before, array $after, string $prefix = ''): array
    {
        $flat = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if ($this->isMap($old) || $this->isMap($new)) {
                $flat += $this->flatten(is_array($old) ? $old : [], is_array($new) ? $new : [], $path);

                continue;
            }

            $flat[$path] = [$old, $new];
        }

        return $flat;
    }

    /**
     * @param  array<string, string>  $labels
     * @return Change|null
     */
    private function change(string $key, mixed $old, mixed $new, array $labels): ?array
    {
        $field = Str::before($key, '.');
        $label = ($labels[$field] ?? Str::headline($field)).($key === $field ? '' : ' · '.Str::headline(Str::after($key, '.')));

        if (is_array($old) || is_array($new)) {
            $removed = $this->rows($key, is_array($old) ? $old : []);
            $added = $this->rows($key, is_array($new) ? $new : []);
            $gone = array_values(array_diff($removed, $added));
            $came = array_values(array_diff($added, $removed));

            if ($gone === [] && $came === [] && $removed === $added) {
                return null;
            }

            // Same rows in another order.
            if ($gone === [] && $came === []) {
                return ['key' => $key, 'label' => $label, 'kind' => 'list', 'before' => '', 'after' => '', 'markup' => null, 'removed' => [], 'added' => ['The order changed.']];
            }

            return ['key' => $key, 'label' => $label, 'kind' => 'list', 'before' => '', 'after' => '', 'markup' => null, 'removed' => $gone, 'added' => $came];
        }

        $before = $this->scalar($key, $old);
        $after = $this->scalar($key, $new);

        if ($before === $after) {
            return null;
        }

        if (in_array($field, $this->driver->htmlFields(), true)) {
            $beforeText = $this->plain((string) $old);
            $afterText = $this->plain((string) $new);

            if ($beforeText === $afterText) {
                return null;
            }

            return ['key' => $key, 'label' => $label, 'kind' => 'html', 'before' => $beforeText, 'after' => $afterText, 'markup' => $this->wordDiff($beforeText, $afterText), 'removed' => [], 'added' => []];
        }

        return ['key' => $key, 'label' => $label, 'kind' => 'text', 'before' => $before, 'after' => $after, 'markup' => null, 'removed' => [], 'added' => []];
    }

    /**
     * One readable line per list entry.
     *
     * @param  array<int|string, mixed>  $list
     * @return list<string>
     */
    private function rows(string $key, array $list): array
    {
        $rows = [];

        foreach ($list as $item) {
            $line = is_array($item) ? $this->row($item) : $this->scalar($key, $item);

            if ($line !== '') {
                $rows[] = $line;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function row(array $row): string
    {
        $lead = null;
        $rest = [];

        // The same row stored in another key order (JSON column vs form) is the same row.
        ksort($row);

        foreach ($row as $field => $value) {
            if (in_array($field, ['id', 'key'], true) || $value === null || $value === '' || $value === []) {
                continue;
            }

            $text = is_array($value) ? implode(', ', array_map(fn ($item): string => $this->scalar($field, $item), $value)) : $this->scalar($field, $value);

            if ($lead === null && in_array($field, ['name', 'title', 'label'], true)) {
                $lead = $text;

                continue;
            }

            $rest[] = Str::headline($field).': '.$text;
        }

        return trim(($lead ?? '').($lead !== null && $rest !== [] ? ' — ' : '').implode(', ', $rest));
    }

    private function scalar(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $field = Str::afterLast($key, '.');
        $kind = $this->idKind($field);

        if ($kind !== null && is_numeric($value)) {
            return $this->names[$kind][(int) $value] ?? "#{$value}";
        }

        return trim((string) $value);
    }

    private function idKind(string $field): ?string
    {
        return match (true) {
            str_ends_with($field, 'asset_id'), $field === 'photo_ids', $field === 'asset_id' => 'media',
            in_array($field, ['location_id', 'parent_id', 'nearby_ids'], true) => 'location',
            $field === 'amenity_ids' => 'amenity',
            in_array($field, ['similar_ids', 'project_ids'], true) => 'project',
            $field === 'category_id' => 'post_category',
            in_array($field, ['default_assignee_id', 'author_user_id'], true) => 'user',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function loadNames(array $before, array $after): void
    {
        $ids = ['media' => [], 'location' => [], 'amenity' => [], 'project' => [], 'user' => [], 'post_category' => []];

        $walk = function (mixed $value, string $field) use (&$walk, &$ids): void {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $walk($item, is_string($key) ? $key : $field);
                }

                return;
            }

            $kind = $this->idKind($field);

            if ($kind !== null && is_numeric($value)) {
                $ids[$kind][] = (int) $value;
            }
        };

        $walk($before, '');
        $walk($after, '');

        $load = fn (string $class, string $column, array $found): array => $found === [] ? [] : $class::query()->whereKey(array_unique($found))->pluck($column, 'id')->map(fn ($name): string => (string) $name)->all();

        $this->names = [
            'media' => $load(MediaAsset::class, 'title', $ids['media']),
            'location' => $load(Location::class, 'name', $ids['location']),
            'amenity' => $load(Amenity::class, 'name', $ids['amenity']),
            'project' => $load(Project::class, 'name', $ids['project']),
            'user' => $load(User::class, 'name', $ids['user']),
            'post_category' => $load(PostCategory::class, 'name', $ids['post_category']),
        ];
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && $value !== [] && ! array_is_list($value);
    }

    /** Rich text as readable plain text: block ends become line breaks, entities are decoded. */
    private function plain(string $html): string
    {
        $text = preg_replace('~</(p|div|li|h[1-6]|tr)>|<br\s*/?>~i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    /** Word-level diff of two plain texts as escaped HTML. */
    private function wordDiff(string $before, string $after): string
    {
        $a = preg_split('/(\s+)/u', $before, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('/(\s+)/u', $after, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($a) * count($b) > self::MAX_CELLS) {
            return '<del>'.$this->escape($before).'</del> <ins>'.$this->escape($after).'</ins>';
        }

        // Longest common subsequence over the tokens.
        $rows = count($a);
        $cols = count($b);
        $table = array_fill(0, $rows + 1, array_fill(0, $cols + 1, 0));

        for ($i = $rows - 1; $i >= 0; $i--) {
            for ($j = $cols - 1; $j >= 0; $j--) {
                $table[$i][$j] = $a[$i] === $b[$j] ? $table[$i + 1][$j + 1] + 1 : max($table[$i + 1][$j], $table[$i][$j + 1]);
            }
        }

        $out = '';
        $i = $j = 0;

        while ($i < $rows && $j < $cols) {
            if ($a[$i] === $b[$j]) {
                $out .= $this->escape($a[$i]);
                $i++;
                $j++;
            } elseif ($table[$i + 1][$j] >= $table[$i][$j + 1]) {
                $out .= $this->mark('del', $a[$i]);
                $i++;
            } else {
                $out .= $this->mark('ins', $b[$j]);
                $j++;
            }
        }

        for (; $i < $rows; $i++) {
            $out .= $this->mark('del', $a[$i]);
        }

        for (; $j < $cols; $j++) {
            $out .= $this->mark('ins', $b[$j]);
        }

        // Neighbouring marks of one kind read as one.
        return preg_replace(['~</del>(\s*)<del>~', '~</ins>(\s*)<ins>~'], '$1', $out) ?? $out;
    }

    private function mark(string $tag, string $token): string
    {
        return trim($token) === '' ? $this->escape($token) : "<{$tag}>".$this->escape($token)."</{$tag}>";
    }

    private function escape(string $text): string
    {
        return nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
    }
}
