<?php

namespace App\Queries\Admin;

use App\Models\ImportRun;
use App\Support\Import\ImportFile;
use App\Support\Import\ImportMapper;
use App\Support\Import\RowOutcome;

/**
 * ADM-IO-001 step 3: the first 20 rows of the file judged with the current mapping, by the same
 * `check` the import itself uses, without saving anything.
 */
final class ImportPreview
{
    /**
     * @return array{
     *     rows: list<array{number: int, outcome: string, label: string, message: string|null, values: array<string, string>}>,
     *     counts: array{create: int, update: int, skip: int, fail: int},
     * }
     */
    public function build(ImportRun $run): array
    {
        $definition = $run->type->definition();
        $mapping = $run->mapping ?? [];
        $rows = [];
        $counts = ['create' => 0, 'update' => 0, 'skip' => 0, 'fail' => 0];

        if (ImportMapper::missingRequired($definition, $mapping) !== []) {
            return ['rows' => [], 'counts' => $counts];
        }

        foreach (ImportFile::rows($run) as $number => $cells) {
            $values = ImportMapper::row($cells, $mapping);
            $result = $definition->check($values);
            $counts[$result->outcome->value]++;

            $rows[] = [
                'number' => $number,
                'outcome' => $result->outcome->value,
                'label' => $this->label($result->outcome),
                'message' => $result->message,
                'values' => $values,
            ];

            if (count($rows) >= ImportFile::PREVIEW_ROWS) {
                break;
            }
        }

        return ['rows' => $rows, 'counts' => $counts];
    }

    private function label(RowOutcome $outcome): string
    {
        return match ($outcome) {
            RowOutcome::Create => 'Will be added',
            RowOutcome::Update => 'Will be updated',
            RowOutcome::Skip => 'Will be skipped',
            RowOutcome::Fail => 'Has a problem',
        };
    }
}
