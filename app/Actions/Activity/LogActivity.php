<?php

namespace App\Actions\Activity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * ADM-LOG-001: one audit row for an admin change, with causer, IP address and user agent.
 * Secrets never go in `$properties`.
 */
final class LogActivity
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(string $log, string $event, string $description, ?User $causer, ?Model $subject = null, array $properties = []): void
    {
        $entry = activity($log)->causedBy($causer)->event($event);

        if ($subject !== null) {
            $entry->performedOn($subject);
        }

        $entry
            ->withProperties([
                ...$properties,
                'ip' => $this->request->ip(),
                'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255),
            ])
            ->log($description);
    }
}
