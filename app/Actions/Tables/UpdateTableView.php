<?php

namespace App\Actions\Tables;

use App\Models\SavedTableView;
use App\Support\Tables\ViewState;
use Illuminate\Support\Facades\DB;

/**
 * ADM-TBL-010: replaces a view's state with the table as it is now, or flips its default flag.
 */
final class UpdateTableView
{
    /**
     * @param  array<mixed>|null  $state  null keeps the stored state
     */
    public function handle(SavedTableView $view, ?array $state, ?bool $default): SavedTableView
    {
        return DB::transaction(function () use ($view, $state, $default): SavedTableView {
            if ($default === true) {
                SavedTableView::query()
                    ->where('user_id', $view->user_id)
                    ->where('table_key', $view->table_key)
                    ->whereKeyNot($view->getKey())
                    ->update(['is_default' => false]);
            }

            if ($state !== null) {
                $view->state = ViewState::clean($state);
            }

            if ($default !== null) {
                $view->is_default = $default;
            }

            $view->save();

            return $view;
        });
    }
}
