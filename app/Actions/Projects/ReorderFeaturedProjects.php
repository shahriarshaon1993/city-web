<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ADM-SCR-02 / CRUD-06: the manual order of the featured projects (home page, FR-PROJ-010). Returns
 * the previous order so the toast can offer Undo.
 */
final class ReorderFeaturedProjects
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<int>  $ids  featured project ids in the new order
     * @return list<int> the order before this change
     */
    public function handle(User $actor, array $ids): array
    {
        return DB::transaction(function () use ($actor, $ids): array {
            $previous = array_values(Project::query()->where('is_featured', true)->orderBy('featured_order')->orderBy('id')->pluck('id')->map(intval(...))->all());

            foreach ($ids as $position => $id) {
                Project::query()->whereKey($id)->where('is_featured', true)->update(['featured_order' => $position + 1]);
            }

            if ($previous !== $ids) {
                $this->logActivity->handle('projects', 'updated', 'Featured projects reordered', $actor, null, ['count' => count($ids)]);
            }

            return $previous;
        });
    }
}
