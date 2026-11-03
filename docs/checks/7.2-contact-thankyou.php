<?php

// php artisan tinker --execute 'require "docs/checks/7.2-contact-thankyou.php";'
// Runs the 7.2 server paths through the HTTP kernel (Inertia headers) and
// prints "expect …" lines. Nothing is stored in Part A, so no rollback is needed.

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Consent\ConsentCookie;
use App\Support\Demo\DemoContent;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// Skip CSRF like the framework's own test runs do.
app()->instance('env', 'testing');

// A GET with `X-Inertia` needs the current asset version, or Inertia answers 409.
$version = app(HandleInertiaRequests::class)->version(Request::create('/', 'GET'));
$inertia = fn (Request $request) => $request->headers->add(['X-Inertia' => 'true', 'X-Inertia-Version' => $version, 'Accept' => 'text/html, application/xhtml+xml']);

// --- /contact ---------------------------------------------------------------
$request = Request::create('/contact?project=gemcon-lakeview&type=site_visit', 'GET');
$inertia($request);
$page = json_decode(app(Kernel::class)->handle($request)->getContent(), true);
echo 'contact component: '.$page['component'].' (expect Site/Contact)'.PHP_EOL;
echo 'contact subject: '.($page['props']['subject'] ?? 'null').' (expect sales)'.PHP_EOL;
echo 'contact project: '.($page['props']['project']['slug'] ?? 'null').' (expect gemcon-lakeview)'.PHP_EOL;
echo 'contact robots: '.$page['props']['seo']['robots'].' (expect index,follow in production; any other environment forces noindex, SEO-TEC-010)'.PHP_EOL;

// --- contact form -> thank-you with the one-request flash ----------------------
$post = Request::create('/forms/contact', 'POST', [
    'name' => 'Rahim Uddin', 'phone' => '01712-345678', 'phone_country' => 'BD', 'consent' => '1',
    'subject' => 'sales', 'message' => str_repeat('hello ', 5), 'project' => 'gemcon-lakeview',
    'form_location' => 'contact_page',
], [], [], ['HTTP_X_INERTIA' => 'true']);
$response = app(Kernel::class)->handle($post);
echo 'contact submit -> '.$response->headers->get('Location').' (expect /thank-you/contact)'.PHP_EOL;
$lead = $post->session()->get('lead');
echo 'flash lead: '.json_encode($lead).' (expect lead_type contact, project_slug gemcon-lakeview, form_location contact_page)'.PHP_EOL;

$get = Request::create('/thank-you/contact', 'GET');
$inertia($get);
$get->setLaravelSession($post->session());
$page = json_decode(app(Kernel::class)->handle($get)->getContent(), true);
echo 'thank-you lead: '.json_encode($page['props']['lead']).' (expect the same fields + reference)'.PHP_EOL;
echo 'thank-you steps: '.count($page['props']['steps']).' (expect 3), related: '.json_encode($page['props']['related']).' (expect null for contact)'.PHP_EOL;
echo 'thank-you robots: '.$page['props']['seo']['robots'].' (expect noindex,nofollow)'.PHP_EOL;

$reload = Request::create('/thank-you/contact', 'GET');
$inertia($reload);
$page = json_decode(app(Kernel::class)->handle($reload)->getContent(), true);
echo 'thank-you reload lead: '.json_encode($page['props']['lead']).' (expect null)'.PHP_EOL;

$projectForm = Request::create('/thank-you/site-visit', 'GET');
$inertia($projectForm);
$page = json_decode(app(Kernel::class)->handle($projectForm)->getContent(), true);
echo 'thank-you site-visit related: '.count($page['props']['related']['projects'] ?? []).' projects (expect 3)'.PHP_EOL;

// --- newsletter ---------------------------------------------------------------
$newsletter = function (array $data) {
    $request = Request::create('/newsletter', 'POST', $data, [], [], [
        'HTTP_X_INERTIA' => 'true',
        'HTTP_REFERER' => url('/'),
    ]);
    $response = app(Kernel::class)->handle($request);
    $bag = $request->session()->get('errors');

    return [$response->getStatusCode(), (array) ($bag['default']['messages'] ?? [])];
};
[, $errors] = $newsletter([]);
echo 'newsletter empty: '.implode(', ', array_keys($errors)).' (expect email, consent)'.PHP_EOL;
[, $errors] = $newsletter(['email' => 'not-an-email', 'consent' => '1']);
echo 'newsletter bad email: '.($errors['email'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[$status, $errors] = $newsletter(['email' => 'reader@example.com', 'consent' => '1']);
echo "newsletter valid: {$status} errors=".json_encode($errors).' (expect 302 back, [])'.PHP_EOL;

// --- consent cookie ---------------------------------------------------------------
$consentVersion = DemoContent::cookies()['version'];
$cookie = fn (string $value) => Request::create('/', 'GET', [], ['gc_consent' => $value]);
echo 'consent current version: '.json_encode(ConsentCookie::read($cookie(json_encode(['version' => $consentVersion, 'analytics' => true, 'marketing' => false])), $consentVersion)).' (expect analytics true, marketing false)'.PHP_EOL;
echo 'consent old version: '.json_encode(ConsentCookie::read($cookie(json_encode(['version' => $consentVersion - 1, 'analytics' => true, 'marketing' => true])), $consentVersion)).' (expect null: re-prompt)'.PHP_EOL;
echo 'consent garbage: '.json_encode(ConsentCookie::read($cookie('nonsense'), $consentVersion)).' (expect null)'.PHP_EOL;
echo 'analytics ids: '.json_encode(DemoContent::analytics()).' (expect both null until ANALYTICS_* is set in .env)'.PHP_EOL;
