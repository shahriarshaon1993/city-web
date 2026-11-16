<?php

namespace App\Support\Redirects;

use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

/**
 * The active redirect rules as an in-memory index (SEO-TEC-008): exact addresses in a hash map,
 * prefix rules longest first. The middleware reads the cached copy (`redirects:v{n}`, raised by
 * every change to a rule, CMS-CACHE-002); the manager builds a fresh one from the database to
 * check a rule before it is saved. `trace()` follows the hops of an address, which is how loops
 * are refused and chains collapsed.
 *
 * @phpstan-type Rule array{id: int|null, source: string, is_prefix: bool, target: string|null, code: int}
 */
final class RuleSet
{
    private const string VERSION_KEY = 'cachever:redirects';

    /** A chain longer than this is treated as a loop. */
    private const int MAX_HOPS = 10;

    private static ?self $current = null;

    /**
     * @param  array<string, Rule>  $exact
     * @param  list<Rule>  $prefix
     */
    private function __construct(private array $exact = [], private array $prefix = []) {}

    /**
     * @param  iterable<Rule>  $rules
     */
    public static function fromRules(iterable $rules): self
    {
        $set = new self;

        foreach ($rules as $rule) {
            $set->add($rule);
        }

        $set->sortPrefixes();

        return $set;
    }

    /** The cached rule set of this request (the middleware's copy). */
    public static function current(): self
    {
        return self::$current ??= self::fromRules(
            Cache::rememberForever('redirects:v'.self::version(), fn (): array => self::loadRows()),
        );
    }

    /** A fresh copy straight from the table, for checks before a save. */
    public static function fromDatabase(): self
    {
        return self::fromRules(self::loadRows());
    }

    /** Called by `Redirect` on every save, delete and restore. */
    public static function bump(): void
    {
        $current = self::version();

        Cache::forever(self::VERSION_KEY, $current + 1);
        Cache::forget("redirects:v{$current}");

        self::$current = null;
    }

    public function count(): int
    {
        return count($this->exact) + count($this->prefix);
    }

    /**
     * This set with one rule added or, when its ID is known, replaced.
     *
     * @param  Rule|Redirect  $rule
     */
    public function withRule(array|Redirect $rule): self
    {
        $rule = $rule instanceof Redirect ? self::toRule($rule) : $rule;
        $copy = $rule['id'] === null ? clone $this : $this->without($rule['id']);
        $copy->add($rule);
        $copy->sortPrefixes();

        return $copy;
    }

    public function without(?int $id): self
    {
        if ($id === null) {
            return clone $this;
        }

        return self::fromRules(array_filter(
            [...array_values($this->exact), ...$this->prefix],
            fn (array $rule): bool => $rule['id'] !== $id,
        ));
    }

    /**
     * The rule that answers an address: an exact rule first, then the longest matching prefix.
     *
     * @return Rule|null
     */
    public function match(string $path): ?array
    {
        if (isset($this->exact[$path])) {
            return $this->exact[$path];
        }

        foreach ($this->prefix as $rule) {
            if ($path === $rule['source'] || str_starts_with($path, rtrim($rule['source'], '/').'/')) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Where a rule sends a request for `$path`: its target, with a trailing `/*` replaced by what
     * follows the prefix. Null for a Gone rule.
     *
     * @param  Rule  $rule
     */
    public function destination(array $rule, string $path): ?string
    {
        $target = $rule['target'];

        if ($target === null || $rule['code'] === 410) {
            return null;
        }

        if ($rule['is_prefix'] && str_ends_with($target, '/*')) {
            $remainder = substr($path, strlen(rtrim($rule['source'], '/')));

            return substr($target, 0, -2).$remainder;
        }

        return $target;
    }

    /**
     * Follows the redirects of an address.
     *
     * `chain` lists every internal path visited, `loop` says an address came round again, `final`
     * is where a visitor ends up (an internal path or an external URL), `gone` that the last hop
     * is a 410 rule, `hops` how many redirects were followed.
     *
     * @return array{chain: list<string>, loop: bool, final: string, gone: bool, hops: int}
     */
    public function trace(string $path): array
    {
        $current = RedirectPath::normalise($path);
        $chain = [$current];
        $hops = 0;

        for ($i = 0; $i < self::MAX_HOPS; $i++) {
            $rule = $this->match($current);

            if ($rule === null) {
                return ['chain' => $chain, 'loop' => false, 'final' => $current, 'gone' => false, 'hops' => $hops];
            }

            $destination = $this->destination($rule, $current);

            if ($destination === null) {
                return ['chain' => $chain, 'loop' => false, 'final' => $current, 'gone' => true, 'hops' => $hops];
            }

            $hops++;
            $own = RedirectPath::ownPath($destination);

            if ($own === null) {
                return ['chain' => $chain, 'loop' => false, 'final' => $destination, 'gone' => false, 'hops' => $hops];
            }

            $next = RedirectPath::normalise($own);
            $looped = in_array($next, $chain, true);
            $chain[] = $next;

            if ($looped) {
                return ['chain' => $chain, 'loop' => true, 'final' => $next, 'gone' => false, 'hops' => $hops];
            }

            $current = $next;
        }

        return ['chain' => $chain, 'loop' => true, 'final' => $current, 'gone' => false, 'hops' => $hops];
    }

    public function loops(string $path): bool
    {
        return $this->trace($path)['loop'];
    }

    /**
     * @param  Rule  $rule
     */
    private function add(array $rule): void
    {
        if ($rule['is_prefix']) {
            $this->prefix[] = $rule;

            return;
        }

        $this->exact[$rule['source']] = $rule;
    }

    private function sortPrefixes(): void
    {
        usort($this->prefix, fn (array $a, array $b): int => strlen($b['source']) <=> strlen($a['source']));
    }

    /**
     * @return Rule
     */
    private static function toRule(Redirect $redirect): array
    {
        return [
            'id' => $redirect->id,
            'source' => $redirect->source,
            'is_prefix' => $redirect->is_prefix,
            'target' => $redirect->target,
            'code' => $redirect->code,
        ];
    }

    /**
     * @return array<int, Rule>
     */
    private static function loadRows(): array
    {
        return Redirect::query()
            ->where('is_active', true)
            ->get(['id', 'source', 'is_prefix', 'target', 'code'])
            ->map(fn (Redirect $redirect): array => self::toRule($redirect))
            ->values()
            ->all();
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
