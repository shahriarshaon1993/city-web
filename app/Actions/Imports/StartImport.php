<?php

namespace App\Actions\Imports;

use App\Actions\Activity\LogActivity;
use App\Enums\ImportStatus;
use App\Jobs\RunImport;
use App\Models\ImportRun;
use App\Models\User;
use App\Support\Import\ImportField;
use App\Support\Import\ImportMapper;
use Illuminate\Validation\ValidationException;

/**
 * ADM-IO-001 step 4: the person has seen the preview and confirms. Every required field must have a
 * column and every confirmation the type asks for must be ticked; then the import is queued.
 */
final class StartImport
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $confirmed  option key → ticked
     *
     * @throws ValidationException
     */
    public function handle(User $actor, ImportRun $run, array $confirmed): void
    {
        if ($run->status !== ImportStatus::Uploaded) {
            throw ValidationException::withMessages(['run' => 'This import has already been started.']);
        }

        $definition = $run->type->definition();
        $missing = ImportMapper::missingRequired($definition, $run->mapping ?? []);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'mapping' => 'Choose a column for '.implode(' and ', array_map(fn (ImportField $field): string => $field->label, $missing)).' first.',
            ]);
        }

        $options = ['file' => $run->original_name];

        if (isset($run->options['delimiter'])) {
            $options['delimiter'] = $run->options['delimiter'];
        }

        foreach ($definition->options() as $option) {
            $ticked = filter_var($confirmed[$option['key']] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($option['required'] && ! $ticked) {
                throw ValidationException::withMessages([$option['key'] => 'Tick the box to confirm before you import.']);
            }

            $options[$option['key']] = $ticked;
        }

        $run->forceFill(['status' => ImportStatus::Queued, 'options' => $options])->save();

        $this->logActivity->handle('imports', 'started', "Import started: {$run->type->label()} from {$run->original_name}", $actor, null, [
            'type' => $run->type->value,
            'rows' => $run->total_rows,
        ]);

        RunImport::dispatch($run->id);
    }
}
