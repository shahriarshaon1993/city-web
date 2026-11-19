<?php

namespace App\Support\Import;

use App\Models\ImportRun;
use DateTimeInterface;
use Generator;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Reads the uploaded file of an import run as plain rows of text, the first row being the header.
 * A CSV is normalised to UTF-8 with a comma/semicolon/tab delimiter when it is uploaded (Excel and
 * Facebook both produce odd ones), so reading is the same for every file.
 */
final class ImportFile
{
    public const int MAX_BYTES = 10 * 1024 * 1024;

    public const int MAX_ROWS = 20000;

    /** Column headers are read from here. */
    public const int PREVIEW_ROWS = 20;

    /**
     * Data rows (not the header) after the first `$skipRows` of them, each keyed by its row number in
     * the file (the header is row 1). All-blank rows are left out but still count as passed.
     *
     * @return Generator<int, list<string>>
     */
    public static function rows(ImportRun $run, int $skipRows = 0): Generator
    {
        $reader = self::reader($run);
        $number = 0;

        try {
            foreach ($reader->getRows() as $cells) {
                $number++;

                if ($number <= $skipRows + 1) {
                    continue;
                }

                $row = self::strings(array_values($cells));

                if (implode('', $row) !== '') {
                    yield $number => $row;
                }
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Rows after the header, blank ones included, stopping one past the limit. A progress bar counts
     * the same way the run does (`processed_rows` is the last row number passed, minus the header).
     */
    public static function countRows(ImportRun $run): int
    {
        $reader = self::reader($run);
        $number = 0;

        try {
            foreach ($reader->getRows() as $unused) {
                if (++$number > self::MAX_ROWS + 1) {
                    break;
                }
            }
        } finally {
            $reader->close();
        }

        return max(0, $number - 1);
    }

    /**
     * @return list<string>
     */
    public static function headers(ImportRun $run): array
    {
        $reader = self::reader($run);

        try {
            foreach ($reader->getRows() as $cells) {
                return self::strings(array_values($cells));
            }
        } finally {
            $reader->close();
        }

        return [];
    }

    public static function absolutePath(ImportRun $run): string
    {
        return Storage::disk(ImportRun::DISK)->path($run->sourcePath());
    }

    private static function reader(ImportRun $run): SimpleExcelReader
    {
        // Blank rows are kept so row numbers in the report match the ones in the spreadsheet.
        $reader = SimpleExcelReader::create(self::absolutePath($run), $run->extension)->noHeaderRow()->preserveEmptyRows();

        if ($run->extension === 'csv') {
            $reader->useDelimiter((string) ($run->options['delimiter'] ?? ','));
        }

        return $reader;
    }

    /**
     * @param  list<mixed>  $cells
     * @return list<string>
     */
    private static function strings(array $cells): array
    {
        return array_map(function (mixed $cell): string {
            if ($cell instanceof DateTimeInterface) {
                return $cell->format('Y-m-d H:i:s');
            }

            return is_scalar($cell) ? trim((string) $cell) : '';
        }, $cells);
    }
}
