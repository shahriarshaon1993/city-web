<?php

// php artisan tinker --execute 'require "docs/checks/7.1-forms.php";'
// Posts the seven lead forms through the HTTP kernel (Inertia headers) and
// prints "expect …" lines. Nothing is stored in 7.1, so no rollback is needed.

use App\Support\Forms\FormOptions;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

// Skip CSRF like the framework's own test runs do.
app()->instance('env', 'testing');

$window = FormOptions::visitWindow();
$errorsOf = function (string $form, array $data, array $files = []) {
    $request = Request::create("/forms/{$form}", 'POST', $data, [], $files, [
        'HTTP_X_INERTIA' => 'true',
        'HTTP_REFERER' => url('/projects/gemcon-lakeview'),
        'HTTP_ACCEPT' => 'text/html, application/xhtml+xml',
    ]);
    $response = app(Kernel::class)->handle($request);
    // The Inertia middleware stores the error bags as plain arrays in the session.
    $bag = $request->session()->get('errors');
    $errors = (array) ($bag['default']['messages'] ?? []);

    return [$response->getStatusCode(), $response->headers->get('Location'), $errors];
};

$base = ['name' => 'Rahim Uddin', 'phone' => '01712-345678', 'phone_country' => 'BD', 'consent' => '1'];

foreach (['project-inquiry', 'site-visit', 'brochure-download', 'callback', 'contact', 'landowner', 'nrb'] as $form) {
    [$status, $location, $errors] = $errorsOf($form, []);
    echo "empty {$form}: ".count($errors).' errors (expect > 0): '.implode(', ', array_keys($errors)).PHP_EOL;
}

[, $location, $errors] = $errorsOf('contact', [...$base, 'subject' => 'sales', 'message' => str_repeat('hello ', 5)]);
echo "contact valid -> {$location} (expect /thank-you/contact) errors=".json_encode($errors).PHP_EOL;

[, , $errors] = $errorsOf('contact', [...$base, 'phone' => '0171', 'subject' => 'sales', 'message' => str_repeat('hello ', 5)]);
echo 'phone 0171: '.($errors['phone'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;

[, , $errors] = $errorsOf('site-visit', [...$base, 'project' => 'gemcon-lakeview', 'visit_date' => $window['min']->subDay()->toDateString(), 'visit_slot' => 'morning']);
echo 'visit yesterday: '.($errors['visit_date'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[, , $errors] = $errorsOf('site-visit', [...$base, 'project' => 'gemcon-lakeview', 'visit_date' => $window['max']->addDay()->toDateString(), 'visit_slot' => 'morning']);
echo 'visit +61 days: '.($errors['visit_date'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[, $location, $errors] = $errorsOf('site-visit', [...$base, 'project' => 'gemcon-lakeview', 'visit_date' => $window['max']->toDateString(), 'visit_slot' => 'morning']);
echo "visit +60 days -> {$location} errors=".json_encode($errors).PHP_EOL;

$pdf = fn (string $name) => UploadedFile::fake()->create($name, 100, 'application/pdf');
[, , $errors] = $errorsOf('landowner', [...$base, 'land_location' => 'Purbachal', 'land_size_katha' => '5.5'], ['documents' => [$pdf('a.pdf'), $pdf('b.pdf'), $pdf('c.pdf'), $pdf('d.pdf')]]);
echo '4 landowner files: '.($errors['documents'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[, , $errors] = $errorsOf('landowner', [...$base, 'land_location' => 'Purbachal', 'land_size_katha' => '5.5'], ['documents' => [UploadedFile::fake()->createWithContent('big.pdf', '%PDF-1.4
'.str_repeat(' ', 6000 * 1024))]]);
echo 'file 6 MB: '.($errors['documents.0'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[, , $errors] = $errorsOf('landowner', [...$base, 'land_location' => 'Purbachal', 'land_size_katha' => '5.5'], ['documents' => [UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload')]]);
echo 'file .exe: '.($errors['documents.0'][0] ?? 'ACCEPTED (unexpected)').PHP_EOL;
[, $location, $errors] = $errorsOf('landowner', [...$base, 'land_location' => 'Purbachal', 'land_size_katha' => '5.5'], ['documents' => [$pdf('a.pdf'), $pdf('b.pdf'), $pdf('c.pdf')]]);
echo "3 landowner files -> {$location} errors=".json_encode($errors).PHP_EOL;

[, , $errors] = $errorsOf('nrb', [...$base, 'phone_country' => 'AE', 'phone' => '050 123 4567', 'country' => 'AE', 'email' => 'a@b.com', 'contact_time' => 'evening']);
echo 'nrb AE national number: '.json_encode($errors).' (expect [])'.PHP_EOL;
