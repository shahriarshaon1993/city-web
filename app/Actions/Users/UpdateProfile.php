<?php

namespace App\Actions\Users;

use App\Actions\Media\SyncMediaUsages;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Own name, email, phone and profile picture from the profile (ADM-SCR-20). A changed email
 * must be confirmed again, so the verification mail goes out right away. The picture is a
 * library item and is recorded as a usage (DM-MUSE field `avatar`).
 */
final class UpdateProfile
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    /**
     * @return bool whether the email changed (and needs confirming)
     */
    public function handle(User $user, string $name, string $email, ?string $phone, ?int $avatarAssetId = null): bool
    {
        $emailChanged = DB::transaction(function () use ($user, $name, $email, $phone, $avatarAssetId): bool {
            $user->fill(['name' => $name, 'email' => $email, 'phone' => $phone]);
            $user->forceFill(['avatar_asset_id' => $avatarAssetId]);

            $emailChanged = $user->isDirty('email');
            $avatarChanged = $user->isDirty('avatar_asset_id');

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($avatarChanged) {
                $this->syncMediaUsages->handle($user, 'avatar', $avatarAssetId === null ? [] : [$avatarAssetId]);
            }

            return $emailChanged;
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $emailChanged;
    }
}
