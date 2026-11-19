<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FR-SOC-004: creates or edits a team member. The slug follows the name once, when the person is
 * created (DM-TEAM); a new person goes to the end of their group.
 */
final class SaveTeamMember
{
    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly SyncMediaUsages $syncMediaUsages,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated form values
     * @return array{member: TeamMember, changed: bool}
     */
    public function handle(User $actor, array $data, ?TeamMember $member = null): array
    {
        return DB::transaction(function () use ($actor, $data, $member): array {
            $creating = $member === null;
            $member ??= new TeamMember(['sort_order' => ((int) TeamMember::query()->max('sort_order')) + 1]);

            $member->fill([
                'name' => trim((string) $data['name']),
                'role_title' => trim((string) $data['role_title']),
                'group' => $data['group'],
                'photo_asset_id' => $data['photo_asset_id'] ?? null,
                'bio' => filled($data['bio'] ?? null) ? trim((string) $data['bio']) : null,
                'linkedin_url' => filled($data['linkedin_url'] ?? null) ? trim((string) $data['linkedin_url']) : null,
                'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
                'show_email' => (bool) ($data['show_email'] ?? false) && filled($data['email'] ?? null),
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);

            if ($creating) {
                $member->slug = $this->freeSlug($member->name);
            }

            $changed = $creating || $member->isDirty();

            if ($changed) {
                $member->save();

                $this->syncMediaUsages->handle($member, 'photo', $member->photo_asset_id === null ? [] : [$member->photo_asset_id]);

                $this->logActivity->handle(
                    'team',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Team member created: ' : 'Team member updated: ').$member->name,
                    $actor,
                    $member,
                    ['label' => $member->name],
                );
            }

            return ['member' => $member, 'changed' => $changed];
        });
    }

    private function freeSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'member', 110, '');
        $candidate = $base;

        for ($number = 2; TeamMember::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-{$number}";
        }

        return $candidate;
    }
}
