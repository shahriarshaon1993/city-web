<?php

namespace App\Support\Toasts;

use Inertia\Inertia;

/**
 * TOAST-01: the one way a controller reports a successful mutation. It flashes
 * `toast: {type, title, description?, action?}` (read by resources/js/lib/notify.ts).
 *
 *   Toast::success('Project saved')->flash();
 *   Toast::success('User moved to Recycle bin')->undo(route(...))->flash();
 *
 * Titles read "{Thing} {past-tense verb}", plain words, up to 60 characters.
 */
final class Toast
{
    /**
     * @param  array{label: string, url: string, method: string, data: array<string, mixed>}|null  $action
     */
    private function __construct(
        private readonly string $type,
        private readonly string $title,
        private ?string $description = null,
        private ?array $action = null,
    ) {}

    public static function success(string $title): self
    {
        return new self('success', $title);
    }

    public static function info(string $title): self
    {
        return new self('info', $title);
    }

    public static function warning(string $title): self
    {
        return new self('warning', $title);
    }

    public static function error(string $title): self
    {
        return new self('error', $title);
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * TOAST-02: an action button (8 s). `method` get = a visit (Open / View), anything else a request.
     *
     * @param  array<string, mixed>  $data
     */
    public function action(string $label, string $url, string $method = 'get', array $data = []): self
    {
        $this->action = ['label' => $label, 'url' => $url, 'method' => $method, 'data' => $data];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function undo(string $url, array $data = []): self
    {
        return $this->action('Undo', $url, 'post', $data);
    }

    /**
     * @return array{type: string, title: string, description: string|null, action: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'action' => $this->action,
        ];
    }

    public function flash(): void
    {
        Inertia::flash('toast', $this->toArray());
    }
}
