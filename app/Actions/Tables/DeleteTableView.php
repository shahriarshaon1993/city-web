<?php

namespace App\Actions\Tables;

use App\Models\SavedTableView;

/**
 * crud.md Saved views: hard delete, own views only (checked by the Policy).
 */
final class DeleteTableView
{
    public function handle(SavedTableView $view): void
    {
        $view->delete();
    }
}
