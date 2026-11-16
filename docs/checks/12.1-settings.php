<?php

// php artisan tinker --execute 'require "docs/checks/12.1-settings.php";'
// Runs the 12.1 settings paths inside a transaction that is rolled back, and prints "expect …" lines.

use App\Actions\Settings\UpdateSettings;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings\ScriptSnippet;
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsRegistry;
use App\Support\Settings\SharedSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

$validate = function (string $group, array $values): array {
    $request = new UpdateSettingsRequest;
    $request->setRouteResolver(fn () => new class($group)
    {
        public function __construct(private string $group) {}

        public function parameter(string $key, $default = null)
        {
            return $this->group;
        }
    });

    return Validator::make(['values' => $values], $request->rules(), [], $request->attributes())->errors()->toArray();
};

DB::beginTransaction();

try {
    Settings::flushMemo();

    $super = User::query()->where('email', 'super-admin@gemcon.test')->first() ?? User::query()->firstOrFail();
    $update = app(UpdateSettings::class);

    // --- Defaults -------------------------------------------------------------------------------------
    echo 'expect hotline default +8809600000000: '.Settings::get('contact', 'hotline').PHP_EOL;
    echo 'expect site name Gemcon City: '.SharedSettings::site()['name'].PHP_EOL;
    echo 'expect 3 cookie categories: '.count(SharedSettings::cookies()['categories']).PHP_EOL;

    // --- Save and read back -----------------------------------------------------------------------------
    $contact = SettingsRegistry::find('contact');
    $changed = $update->handle($super, $contact, ['hotline' => '+880 9611-111111', 'phones' => ['+8801711111111', ''], 'emails' => ['a@example.com'], 'whatsapp_number' => '+8801711111111', 'office_hours_text' => 'Daily']);
    echo 'expect changed fields (5): '.implode(', ', $changed).PHP_EOL;
    echo 'expect new hotline in site(): '.SharedSettings::site()['contact']['hotline'].PHP_EOL;
    echo 'expect blank phone dropped (1): '.count(Settings::get('contact', 'phones')).PHP_EOL;
    echo 'expect second save changes nothing (0): '.count($update->handle($super, $contact, ['hotline' => '+880 9611-111111', 'phones' => ['+8801711111111'], 'emails' => ['a@example.com'], 'whatsapp_number' => '+8801711111111', 'office_hours_text' => 'Daily'])).PHP_EOL;

    // --- Activity log: key-level diff, secrets without values ----------------------------------------------
    $hook = SettingsRegistry::find('webhook');
    $update->handle($super, $hook, ['enabled' => true, 'url' => 'https://example.com/hook', 'secret' => 'top-secret-value']);
    $entry = Activity::query()->where('log_name', 'settings')->latest('id')->first();
    echo 'expect secret logged as "changed" and no plaintext: '.json_encode($entry->properties['changes']['secret'] ?? null).' / leaked='.(str_contains($entry->properties->toJson(), 'top-secret-value') ? 'YES' : 'no').PHP_EOL;
    echo 'expect stored secret is ciphertext, decrypts to value: '.(Setting::query()->where('key', 'secret')->value('value') !== 'top-secret-value' ? 'ciphertext' : 'PLAINTEXT').' / '.Settings::secret('webhook', 'secret').PHP_EOL;
    echo 'expect group() never has the secret: '.(array_key_exists('secret', Settings::group('webhook')) ? 'LEAKED' : 'absent').PHP_EOL;
    $update->handle($super, $hook, ['enabled' => true, 'url' => 'https://example.com/hook', 'secret' => null]);
    echo 'expect blank keeps the secret: '.Settings::secret('webhook', 'secret').PHP_EOL;
    $update->handle($super, $hook, ['enabled' => true, 'url' => 'https://example.com/hook', 'secret' => null], ['secret']);
    echo 'expect clear removes it (null): '.var_export(Settings::secret('webhook', 'secret'), true).PHP_EOL;

    // --- Validation ---------------------------------------------------------------------------------------
    $errors = $validate('analytics', ['gtm_id' => 'GTM-1', 'ga4_id' => 'G-ABCDEFGH12']);
    echo 'expect bad GTM ID refused: '.json_encode($errors['values.gtm_id'] ?? null).PHP_EOL;
    echo 'expect good IDs accepted (0 errors): '.count($validate('analytics', ['gtm_id' => 'gtm-abc1234', 'ga4_id' => null])).PHP_EOL;
    $errors = $validate('social', ['facebook' => 'https://example.com/x', 'instagram' => null, 'linkedin' => null, 'youtube' => null, 'x' => null]);
    echo 'expect wrong social host refused: '.json_encode($errors['values.facebook'] ?? null).PHP_EOL;
    $errors = $validate('seo', ['title_template' => 'No token here', 'home_title' => 'x', 'home_description' => 'x', 'project_title_template' => '{nope}', 'project_description_template' => 'x', 'location_title_template' => 'x', 'location_segment_title_template' => 'x', 'noindex' => false]);
    echo 'expect title template without {title} refused: '.json_encode($errors['values.title_template'] ?? null).PHP_EOL;
    echo 'expect unknown {nope} token refused: '.json_encode($errors['values.project_title_template'] ?? null).PHP_EOL;

    // --- Scripts allowlist --------------------------------------------------------------------------------
    foreach ([
        'allowed src' => '<script async src="https://www.googletagmanager.com/gtag/js?id=G-ABCDEFGH12"></script>',
        'inline script' => '<script>alert(1)</script>',
        'unknown host' => '<script src="https://evil.example/x.js"></script>',
        'onload attr' => '<script src="https://clarity.ms/x.js" onload="x()"></script>',
        'noscript pixel' => '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1&ev=PageView"></noscript>',
        'div' => '<div>hi</div>',
    ] as $name => $html) {
        $problems = ScriptSnippet::inspect($html)['problems'];
        echo "expect scripts [$name]: ".($problems === [] ? 'accepted' : 'refused — '.$problems[0]).PHP_EOL;
    }
} finally {
    DB::rollBack();
    Settings::flushMemo();
}

echo 'expect rollback left no rows (0): '.Setting::query()->count().PHP_EOL;
