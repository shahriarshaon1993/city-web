<?php

namespace App\Actions\Tables;

use App\Models\SavedTableView;
use App\Models\User;
use App\Support\Tables\ViewState;
use Illuminate\Support\Facades\DB;

/**
 * ADM-TBL-010: stores the current table state under a name, personal or shared with everyone
 * who can open the table (sharing needs `views.share`, checked by the Form Request).
 */
final class SaveTableView
{
    /**
     * @param  array<mixed>  $state
     */
    public function handle(User $user, string $tableKey, string $name, array $state, bool $shared, bool $default): SavedTableView
    {
        return DB::transaction(function () use ($user, $tableKey, $name, $state, $shared, $default): SavedTableView {
            if ($default) {
                SavedTableView::query()->where('user_id', $user->getKey())->where('table_key', $tableKey)->update(['is_default' => false]);
            }

            $view = new SavedTableView([
                'table_key' => $tableKey,
                'name' => $name,
                'state' => ViewState::clean($state),
                'is_shared' => $shared,
                'is_default' => $default,
            ]);
            $view->user()->associate($user);
            $view->save();

            return $view;
        });
    }
}
