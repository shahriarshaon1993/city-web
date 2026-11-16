<?php

namespace App\Actions\Seo;

use App\Actions\Activity\LogActivity;
use App\Models\Setting;
use App\Models\User;
use App\Support\Redirects\RobotsTxt;
use App\Support\Settings\Settings;

/**
 * SEO-TEC-010: saves the robots.txt body as `seo.robots_body`. A blank body goes back to the
 * default. Returns false when nothing would change.
 */
final class UpdateRobotsTxt
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, ?string $body): bool
    {
        $body = $body === null || trim($body) === '' ? null : preg_replace('/\R/', "\n", trim($body))."\n";

        if ($body === RobotsTxt::body()) {
            return false;
        }

        if ($body === null && ! RobotsTxt::isCustom()) {
            return false;
        }

        $row = Setting::query()->firstOrNew(['group' => 'seo', 'key' => 'robots_body']);

        if ($body === null) {
            if ($row->exists) {
                $row->delete();
            }
        } else {
            $row->fill(['value' => $body, 'is_secret' => false, 'updated_by' => $actor->id])->save();
        }

        Settings::bump();

        $this->logActivity->handle('seo', 'updated', 'robots.txt '.($body === null ? 'reset to the default' : 'changed'), $actor, null, [
            'lines' => $body === null ? null : substr_count($body, "\n"),
        ]);

        return true;
    }
}
