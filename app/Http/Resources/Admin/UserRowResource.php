<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use App\Support\Users\UserGuard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the users table (ADM-SCR-16). `can` / `blocked` carry what the signed-in
 * user may do, so the UI hides or disables actions with a reason (ARC-005, HELP-08);
 * the Actions re-check on the server.
 *
 * @mixin User
 */
class UserRowResource extends JsonResource
{
    public static function statusOf(User $user): string
    {
        return match (true) {
            ! $user->is_active => 'inactive',
            $user->email_verified_at === null && $user->last_login_at === null => 'invited',
            default => 'active',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $status = self::statusOf($this->resource);
        $editBlock = UserGuard::editBlock($actor, $this->resource);
        $deactivateBlock = $this->is_active ? UserGuard::deactivateBlock($actor, $this->resource) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->getRoleNames()->first(),
            'status' => $status,
            'is_active' => $this->is_active,
            'is_self' => $actor->is($this->resource),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => [
                'edit' => $editBlock === null,
                'resend' => $status === 'invited' && $editBlock === null,
                'toggle' => $this->is_active ? $deactivateBlock === null : $editBlock === null,
            ],
            'blocked' => [
                'edit' => $editBlock,
                'toggle' => $this->is_active ? $deactivateBlock : $editBlock,
            ],
        ];
    }
}
