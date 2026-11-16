<?php

namespace App\Actions\Monitoring;

use App\Actions\Activity\LogActivity;
use App\Models\Setting;
use App\Models\User;
use App\Support\Redirects\NotFoundIgnore;
use App\Support\Settings\Settings;

/**
 * SEO-TEC-009: saves the regex ignore list of the 404 monitor as `seo.not_found_ignore`. `null`
 * goes back to the built-in defaults; an empty list means nothing is ignored.
 */
final class UpdateNotFoundIgnoreList
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, ?string $text): void
    {
        $row = Setting::query()->firstOrNew(['group' => 'seo', 'key' => 'not_found_ignore']);

        if ($text === null) {
            if ($row->exists) {
                $row->delete();
            }
        } else {
            $row->fill(['value' => implode("\n", NotFoundIgnore::split($text)), 'is_secret' => false, 'updated_by' => $actor->id])->save();
        }

        Settings::bump();

        $this->logActivity->handle('seo', 'updated', '404 ignore list '.($text === null ? 'reset to defaults' : 'changed'), $actor, null, [
            'patterns' => NotFoundIgnore::patterns(),
        ]);
    }
}
