<?php

namespace App\Support\Import;

/**
 * What one file row does. In the preview it is a prediction ("will be created"); in the report it is
 * what happened. `Skip` is a row that is fine but needs nothing (it exists already), `Fail` one the
 * rules refuse.
 */
enum RowOutcome: string
{
    case Create = 'create';
    case Update = 'update';
    case Skip = 'skip';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'New',
            self::Update => 'Updates',
            self::Skip => 'Skipped',
            self::Fail => 'Problem',
        };
    }
}
