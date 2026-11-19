<?php

namespace App\Enums;

/** `import_runs.status`: uploaded (mapping and preview) → queued → running → finished | failed. */
enum ImportStatus: string implements Labelled
{
    case Uploaded = 'uploaded';
    case Queued = 'queued';
    case Running = 'running';
    case Finished = 'finished';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Waiting for you',
            self::Queued => 'Queued',
            self::Running => 'Importing',
            self::Finished => 'Finished',
            self::Failed => 'Failed',
        };
    }

    public function isWorking(): bool
    {
        return $this === self::Queued || $this === self::Running;
    }
}
