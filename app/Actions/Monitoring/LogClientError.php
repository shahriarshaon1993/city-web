<?php

namespace App\Actions\Monitoring;

use Illuminate\Support\Facades\Log;

/**
 * Writes a browser error to the application log (SEO-ANA-006); the admin
 * "Errors" view of 18.2 reads it from there.
 */
final class LogClientError
{
    public function handle(string $source, string $message, ?string $url, ?string $userAgent): void
    {
        Log::warning('Client error: '.$source, [
            'message' => $message,
            'url' => $url,
            'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
        ]);
    }
}
