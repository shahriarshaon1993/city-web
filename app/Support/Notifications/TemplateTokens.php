<?php

namespace App\Support\Notifications;

/**
 * Fills the `{name}` / `{project}` tokens of a setting (autoresponder subject and body). Values are
 * collapsed to one line so a visitor's text can never add a header line to the email.
 */
final class TemplateTokens
{
    /**
     * @param  array<string, string>  $values
     */
    public static function fill(string $template, array $values): string
    {
        $replace = [];

        foreach ($values as $token => $value) {
            $replace['{'.$token.'}'] = trim((string) preg_replace('/\s+/u', ' ', $value));
        }

        return strtr($template, $replace);
    }
}
