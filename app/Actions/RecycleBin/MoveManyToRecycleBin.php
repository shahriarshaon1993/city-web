<?php

namespace App\Actions\RecycleBin;

use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * DEL-02 bulk delete: each item goes through MoveToRecycleBin, so the same guards apply;
 * refused items are counted and explained, never silently skipped (TOAST-06).
 */
final class MoveManyToRecycleBin
{
    public function __construct(private readonly MoveToRecycleBin $moveToRecycleBin) {}

    /**
     * @param  iterable<int, Model&Trashable>  $items
     * @return array{entries: list<RecycleBinEntry>, refused: list<string>}
     */
    public function handle(User $actor, iterable $items): array
    {
        $entries = [];
        $refused = [];

        foreach ($items as $item) {
            try {
                $entries[] = $this->moveToRecycleBin->handle($actor, $item);
            } catch (ValidationException $exception) {
                $refused[] = $item->binLabel().': '.$exception->validator->errors()->first();
            }
        }

        return ['entries' => $entries, 'refused' => $refused];
    }
}
