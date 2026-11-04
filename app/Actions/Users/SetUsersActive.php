<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * ADM-TBL-009 bulk activate / deactivate: each user goes through SetUserActive, so the same
 * limits apply; refused users are counted and explained, never silently skipped.
 */
final class SetUsersActive
{
    public function __construct(private readonly SetUserActive $setUserActive) {}

    /**
     * @param  Collection<int, User>  $users
     * @return array{changed: int, unchanged: int, refused: list<string>}
     */
    public function handle(User $actor, Collection $users, bool $active): array
    {
        $changed = 0;
        $unchanged = 0;
        $refused = [];

        foreach ($users as $user) {
            try {
                $this->setUserActive->handle($actor, $user, $active) ? $changed++ : $unchanged++;
            } catch (ValidationException $exception) {
                $refused[] = $user->name.': '.$exception->validator->errors()->first();
            }
        }

        return ['changed' => $changed, 'unchanged' => $unchanged, 'refused' => $refused];
    }
}
