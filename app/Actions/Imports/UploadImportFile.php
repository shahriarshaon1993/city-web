<?php

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Models\ImportRun;
use App\Models\User;
use App\Support\Import\ImportFile;
use App\Support\Import\ImportMapper;
use App\Support\Import\ImportType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * ADM-IO-001 step 1: keeps the uploaded file on the private disk, makes a CSV readable (UTF-16 from
 * Facebook, a Windows code page, a `;` or tab delimiter), checks it has rows and no more than 20,000,
 * and guesses the column mapping from the header row.
 */
final class UploadImportFile
{
    /**
     * @throws ValidationException when the file cannot be used
     */
    public function handle(User $actor, ImportType $type, UploadedFile $file): ImportRun
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $extension = $extension === 'txt' ? 'csv' : $extension;

        $run = ImportRun::create([
            'user_id' => $actor->id,
            'type' => $type,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 190),
            'extension' => $extension,
            'status' => ImportStatus::Uploaded,
        ]);

        $disk = Storage::disk(ImportRun::DISK);

        try {
            $disk->putFileAs($run->directory(), $file, "source.{$extension}");

            if ($extension === 'csv') {
                $run->options = ['delimiter' => $this->makeCsvReadable($disk->path($run->sourcePath()))];
                $run->save();
            }

            $headers = ImportFile::headers($run);
            $total = ImportFile::countRows($run);
        } catch (Throwable $exception) {
            report($exception);
            $this->discard($run);

            throw ValidationException::withMessages(['file' => 'This file could not be read. Save it as .xlsx or .csv from your spreadsheet and try again.']);
        }

        if (implode('', $headers) === '') {
            $this->discard($run);

            throw ValidationException::withMessages(['file' => 'The first row must hold the column names.']);
        }

        if ($total === 0) {
            $this->discard($run);

            throw ValidationException::withMessages(['file' => 'The file has a header row but no data rows.']);
        }

        if ($total > ImportFile::MAX_ROWS) {
            $this->discard($run);

            throw ValidationException::withMessages(['file' => 'The file has more than '.number_format(ImportFile::MAX_ROWS).' rows. Split it into smaller files.']);
        }

        $run->forceFill([
            'total_rows' => $total,
            'mapping' => ImportMapper::auto($type->definition(), $headers),
        ])->save();

        return $run;
    }

    /**
     * Rewrites the file as UTF-8 without a BOM and returns its delimiter.
     */
    private function makeCsvReadable(string $path): string
    {
        $text = (string) file_get_contents($path);

        if (str_starts_with($text, "\xFF\xFE")) {
            $text = mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($text, "\xFE\xFF")) {
            $text = mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        } elseif (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        $text = str_replace("\r\n", "\n", $text);
        file_put_contents($path, $text);

        $firstLine = (string) strtok($text, "\n");
        $best = ',';
        $bestCount = 0;

        foreach ([',', ';', "\t"] as $delimiter) {
            $count = substr_count($firstLine, $delimiter);

            if ($count > $bestCount) {
                $best = $delimiter;
                $bestCount = $count;
            }
        }

        return $best;
    }

    private function discard(ImportRun $run): void
    {
        Storage::disk(ImportRun::DISK)->deleteDirectory($run->directory());
        $run->delete();
    }
}
