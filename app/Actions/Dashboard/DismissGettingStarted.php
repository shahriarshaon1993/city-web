<?php

namespace App\Actions\Dashboard;

use App\Actions\Activity\LogActivity;
use App\Models\Setting;
use App\Models\User;
use App\Support\Admin\GettingStarted;
use App\Support\Settings\Settings;

/**
 * HELP-05: hides the "Getting started" card for everyone (it describes the site, not a person).
 */
final class DismissGettingStarted
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor): void
    {
        Setting::query()->updateOrCreate(
            ['group' => GettingStarted::DISMISSED_GROUP, 'key' => GettingStarted::DISMISSED_KEY],
            ['value' => true, 'is_secret' => false, 'updated_by' => $actor->id],
        );

        Settings::bump();

        $this->logActivity->handle('settings', 'updated', 'Getting started checklist dismissed', $actor);
    }
}
