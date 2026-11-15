<?php

namespace App\Actions\RecycleBin;

use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Support\RecycleBin\RestoreConflictException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Bulk restore and the Undo of a bulk delete. An item that needs a decision is skipped with
 * its reason (TOAST-06); it can be restored on its own, where the dialog asks.
 */
final class RestoreManyFromRecycleBin
{
    public function __construct(private readonly RestoreFromRecycleBin $restoreFromRecycleBin) {}

    /**
     * @param  Collection<int, RecycleBinEntry>  $entries
     * @return array{restored: int, refused: list<string>}
     */
    public function handle(User $actor, Collection $entries): array
    {
        $restored = 0;
        $refused = [];

        foreach ($entries as $entry) {
            try {
                $this->restoreFromRecycleBin->handle($actor, $entry);
                $restored++;
            } catch (RestoreConflictException $exception) {
                $refused[] = $entry->label.': '.$exception->conflicts[0]->message;
            } catch (ValidationException $exception) {
                $refused[] = $entry->label.': '.$exception->validator->errors()->first();
            }
        }

        return ['restored' => $restored, 'refused' => $refused];
    }
}
