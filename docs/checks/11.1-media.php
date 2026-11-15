<?php

/**
 * Phase 11.1 check: upload limits (type by content, size, pixels, hero, SVG), conversions, the
 * MediaImage shape, usage guard and the Recycle bin for media. Rows are rolled back and the files
 * it writes are deleted. Needs `php artisan db:seed` (roles + one user per role) and the 11.1 migrations.
 * Run: php -d memory_limit=1G artisan tinker docs/checks/11.1-media.php
 * (The real queue is checked by docs/checks/11.1-media-queue.php.)
 */

use App\Actions\Media\GenerateConversions;
use App\Actions\Media\UploadMedia;
use App\Actions\RecycleBin\DeleteForever;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\RecycleBin\RestoreFromRecycleBin;
use App\Http\Resources\Media\MediaDocumentResource;
use App\Http\Resources\Media\MediaImageResource;
use App\Jobs\Media\GenerateMediaConversions;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\User;
use App\Support\Auth\SystemRole;
use App\Support\Demo\DemoMedia;
use App\Support\Media\UploadPurpose;
use App\Support\RecycleBin\BinTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ').$label.': '.json_encode($got).($got === $expect ? '' : ' (expect '.json_encode($expect).')')."\n";
};
$as = fn (SystemRole $role) => User::role($role->value)->where('is_active', true)->orderBy('id')->firstOrFail();
$refused = function (callable $fn): ?string {
    try {
        $fn();

        return null;
    } catch (ValidationException $exception) {
        return $exception->validator->errors()->first();
    }
};

$work = storage_path('app/media-check');
File::deleteDirectory($work);
File::ensureDirectoryExists($work);

/** A GD gradient photo with a bright "subject" on the right, so the focal crop is visible. */
$photo = function (string $name, int $width, int $height, string $format = 'jpg') use ($work): string {
    $image = imagecreatetruecolor($width, $height);
    for ($y = 0; $y < $height; $y += 8) {
        imagefilledrectangle($image, 0, $y, $width, min($height, $y + 8), imagecolorallocate($image, 40 + (int) ($y / $height * 120), 90, 160 - (int) ($y / $height * 80)));
    }
    imagefilledellipse($image, (int) ($width * 0.8), (int) ($height * 0.5), (int) ($width * 0.2), (int) ($width * 0.2), imagecolorallocate($image, 250, 220, 120));
    $path = "{$work}/{$name}";
    match ($format) {
        'png' => imagepng($image, $path),
        'avif' => imageavif($image, $path, 50),
        default => imagejpeg($image, $path, 80),
    };

    return $path;
};
$upload = fn (string $path, ?string $name = null) => new UploadedFile($path, $name ?? basename($path), null, null, true);
$created = [];
$uploadAs = function (User $user, string $path, UploadPurpose $purpose = UploadPurpose::Library, ?string $name = null) use ($upload, &$created) {
    $asset = app(UploadMedia::class)->handle($user, $upload($path, $name), $purpose);
    $created[] = $asset->id;

    return $asset;
};
$say('GD has WebP', function_exists('imagewebp'), true);
$avif = (gd_info()['AVIF Support'] ?? false) === true;
echo 'info GD AVIF support: '.json_encode($avif)."\n";

DB::beginTransaction();

try {
    $super = $as(SystemRole::SuperAdmin);
    $admin = $as(SystemRole::Admin);
    $editor = $as(SystemRole::ContentEditor);
    $sales = $as(SystemRole::SalesExecutive);
    $viewer = $as(SystemRole::Viewer);

    // --- permissions (spec/admin.md §10.2)
    $say('Content Editor may upload', $editor->can('create', MediaAsset::class), true);
    $say('Sales Executive may not upload', $sales->can('create', MediaAsset::class), false);
    $say('Viewer may not upload', $viewer->can('create', MediaAsset::class), false);
    $say('Content Editor sees media in the bin (unused only)', in_array('media_asset', BinTypes::typesFor($editor), true), true);
    $say('route admin.media.store exists', str_ends_with(route('admin.media.store'), '/admin/media'), true);

    // --- a 6000×4000 photo: stored, queued, converted
    $big = $uploadAs($editor, $photo('big.jpg', 6000, 4000));
    $media = $big->file();
    $say('big: kind', $big->kind->value, 'image');
    $say('big: size read automatically', [$big->width, $big->height], [6000, 4000]);
    $say('big: waits for the queue', $big->conversions_status, 'pending');
    $say('big: storage name = 12-char hash', preg_match('/^[0-9a-f]{12}\.jpg$/', $media->file_name), 1);
    $say('big: stored under media/{id}/', $media->getPathRelativeToRoot(), "media/{$media->id}/{$media->file_name}");
    $say('big: original is on the disk', Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()), true);
    $job = new GenerateMediaConversions($media);
    $say('job: media queue, 3 tries, back-off 60/300/900', [$job->queue, $job->tries, $job->backoff()], ['media', 3, [60, 300, 900]]);
    $say('activity: uploaded', Activity::query()->where('log_name', 'media')->where('event', 'uploaded')->where('subject_id', $big->id)->exists(), true);

    $started = microtime(true);
    app(GenerateConversions::class)->handle($media);
    echo 'info conversions took '.round(microtime(true) - $started, 1)." s\n";
    $big->refresh();
    $r = $big->renditions();
    $say('big: ready', $big->conversions_status, 'ready');
    $say('big: webp widths (library + hero set)', array_column($r->webp, 'width'), [400, 640, 800, 960, 1280, 1600, 1920, 2560]);
    $say('big: avif widths', array_column($r->avif, 'width'), $avif ? [400, 640, 800, 960, 1280, 1600, 1920, 2560] : []);
    $say('big: jpeg widths = HERO-011 set', array_column($r->jpeg, 'width'), [640, 960, 1280, 1600, 1920, 2560]);
    $say('big: fallback is the 1920 JPEG', str_ends_with($r->fallback, '-w1920.jpg'), true);
    $say('big: file names carry the hash', str_contains($r->fallback, pathinfo($media->file_name, PATHINFO_FILENAME)), true);
    $files = collect($r->paths())->push($r->fallback);
    $say('big: every file exists', $files->every(fn ($path) => Storage::disk($media->disk)->exists($path)), true);
    $og = getimagesize(Storage::disk($media->disk)->path($r->og));
    $say('big: og crop is 1200×630', [$og[0], $og[1]], [1200, 630]);
    $say('big: dominant colour', preg_match('/^#[0-9a-f]{6}$/', (string) $big->dominant_color), 1);
    $say('big: og url', str_contains((string) $big->ogUrl(), '/storage/media/'), true);

    // --- the media resource has the demo image's shape (DEC-31)
    $shape = (new MediaImageResource($big))->resolve();
    $demoKeys = ['alt', 'decorative', 'caption', 'credit', 'width', 'height', 'focal', 'dominant_color', 'sources', 'fallback'];
    $say('resource keys = DemoMedia keys', array_keys($shape), $demoKeys);
    $say('resource sources keys', array_keys($shape['sources']), ['avif', 'webp', 'jpeg']);
    $say('resource webp srcset', preg_match('#^http\S+/storage/media/\d+/\S+-w400\.webp 400w, #', $shape['sources']['webp']), 1);
    $say('resource jpeg srcset (hero-sized photo)', is_string($shape['sources']['jpeg']), true);
    $say('resource focal default', $shape['focal'], ['x' => 50, 'y' => 50]);
    $say('resource fallback is a JPEG', str_ends_with($shape['fallback'], '.jpg'), true);
    $demo = DemoMedia::keys() === [] ? null : DemoMedia::image(DemoMedia::keys()[0]);
    echo $demo === null ? "info no demo images generated, skipped the key comparison with DemoMedia\n" : '';
    $demo !== null && $say('same keys as DemoMedia::image()', array_keys($shape), array_keys($demo));
    $big->forceFill(['is_decorative' => true, 'alt' => 'x'])->save();
    $say('decorative → alt is empty (HERO-014)', (new MediaImageResource($big->refresh()))->resolve()['alt'], '');

    // --- a small photo: only the widths it has, one JPEG
    $small = $uploadAs($editor, $photo('small.jpg', 1000, 800));
    app(GenerateConversions::class)->handle($small->file());
    $sr = $small->refresh()->renditions();
    $say('small: widths', array_column($sr->webp, 'width'), [400, 800]);
    $say('small: one JPEG (800), no hero set', array_column($sr->jpeg, 'width'), [800]);
    $say('small: jpeg srcset is null, fallback alone', (new MediaImageResource($small))->resolve()['sources']['jpeg'], null);

    // --- a PNG source
    $png = $uploadAs($editor, $photo('logo.png', 900, 600, 'png'));
    app(GenerateConversions::class)->handle($png->file());
    $say('png: converted', $png->refresh()->conversions_status, 'ready');

    if ($avif) {
        $avifAsset = $uploadAs($editor, $photo('photo.avif', 1200, 800, 'avif'));
        $say('avif upload accepted', $avifAsset->kind->value, 'image');
    }

    // --- limits (CMS-MED-003 / 006)
    $huge = imagecreate(7000, 6500);
    imagecolorallocate($huge, 255, 255, 255);
    imagepng($huge, "{$work}/huge.png");
    $message = $refused(fn () => $uploadAs($editor, "{$work}/huge.png"));
    $say('45 MP image refused, limit named', [str_contains((string) $message, '40 megapixels'), str_contains((string) $message, '45.5 megapixels')], [true, true]);

    file_put_contents("{$work}/notes.jpg", "just some text\n");
    $say('text renamed .jpg refused by content', str_contains((string) $refused(fn () => $uploadAs($editor, "{$work}/notes.jpg")), 'not a file type'), true);

    copy($photo('real.jpg', 800, 600), "{$work}/renamed.png");
    $say('JPEG renamed .png refused', str_contains((string) $refused(fn () => $uploadAs($editor, "{$work}/renamed.png")), 'its content is JPEG'), true);

    file_put_contents("{$work}/script.php.jpg", 'x');
    $say('double extension refused', $refused(fn () => $uploadAs($editor, "{$work}/script.php.jpg")) !== null, true);

    // --- SVG (NFR-SEC-12)
    file_put_contents("{$work}/mark.svg", '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 120 60" onload="alert(1)"><script>alert(1)</script><a xlink:href="javascript:alert(2)"><rect width="120" height="60" fill="#0f766e" onclick="alert(3)"/></a><image href="https://evil.example/x.png"/></svg>');
    $svg = $uploadAs($editor, "{$work}/mark.svg");
    $stored = Storage::disk($svg->file()->disk)->get($svg->file()->getPathRelativeToRoot());
    $say('svg: script removed', [str_contains($stored, '<script'), str_contains($stored, 'onload'), str_contains($stored, 'onclick'), str_contains($stored, 'javascript:'), str_contains($stored, 'evil.example')], [false, false, false, false, false]);
    $say('svg: shape kept', str_contains($stored, '<rect'), true);
    $say('svg: kind, size from viewBox, ready', [$svg->kind->value, $svg->width, $svg->height, $svg->conversions_status], ['svg', 120, 60, 'ready']);
    $svgShape = (new MediaImageResource($svg))->resolve();
    $say('svg: DEC-47 shape', [$svgShape['sources'], $svgShape['dominant_color'], str_ends_with($svgShape['fallback'], '.svg')], [['avif' => null, 'webp' => '', 'jpeg' => null], null, true]);
    file_put_contents("{$work}/bigmark.svg", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><!-- '.str_repeat('x', 1_200_000).' --></svg>');
    $say('svg over 1 MB refused', str_contains((string) $refused(fn () => $uploadAs($editor, "{$work}/bigmark.svg")), 'at most 1 MB'), true);

    // --- hero profile (HERO-010 / HERO-022)
    $message = $refused(fn () => $uploadAs($editor, $photo('narrow.jpg', 1600, 900), UploadPurpose::HeroImage));
    $say('hero image below 1920×1080 refused', str_contains((string) $message, 'at least 1920 × 1080'), true);
    $heroPhoto = $uploadAs($editor, $photo('hero.jpg', 2400, 1350), UploadPurpose::HeroImage);
    $say('hero image 2400×1350 accepted', $heroPhoto->kind->value, 'image');
    $message = $refused(fn () => $uploadAs($editor, "{$work}/mark.svg", UploadPurpose::HeroImage));
    $say('hero image cannot be an SVG', str_contains((string) $message, 'must be a JPEG, PNG, WebP or AVIF'), true);

    file_put_contents("{$work}/hero.mp4", "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 7 * 1024 * 1024));
    $message = $refused(fn () => $uploadAs($editor, "{$work}/hero.mp4", UploadPurpose::HeroVideo));
    $say('7 MB hero MP4 refused, limit named', [str_contains((string) $message, 'at most 6 MB'), str_contains((string) $message, 'this file is 7 MB')], [true, true]);
    $say('same file as a library video passes the size check, then cannot be read', str_contains((string) $refused(fn () => $uploadAs($editor, "{$work}/hero.mp4")), 'cannot be read'), true);

    file_put_contents("{$work}/hero.webm", "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm".str_repeat("\x00", 6 * 1024 * 1024));
    $message = $refused(fn () => $uploadAs($editor, "{$work}/hero.webm", UploadPurpose::HeroVideo));
    $say('6 MB hero WebM refused, limit named', str_contains((string) $message, 'at most 5 MB'), true);

    $demoVideo = storage_path('app/demo-src/video/hero.mp4');

    if (File::exists($demoVideo)) {
        $video = $uploadAs($editor, $demoVideo, UploadPurpose::HeroVideo);
        $say('demo hero.mp4 accepted: size and duration read from the header', [$video->width !== null, $video->duration_s !== null], [true, true]);
    } else {
        echo "info storage/app/demo-src/video/hero.mp4 not present — upload your own MP4 to check duration / fps / resolution limits by hand\n";
    }

    // --- PDF
    file_put_contents("{$work}/brochure.pdf", "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
    $pdf = $uploadAs($editor, "{$work}/brochure.pdf");
    $say('pdf: kind, ready, no conversions', [$pdf->kind->value, $pdf->conversions_status, $pdf->renditions()], ['document', 'ready', null]);
    $say('pdf: document shape', array_keys((new MediaDocumentResource($pdf))->resolve()), ['url', 'bytes']);

    // --- usage guard + Recycle bin (CMS-MED-008, DEL-03, DEL-06)
    $usage = MediaUsage::query()->create(['media_asset_id' => $small->id, 'usable_type' => 'user', 'usable_id' => $editor->id, 'field' => 'avatar', 'position' => 0]);
    $block = $small->binUsageBlock($editor);
    $say('used asset: delete blocked with the count', str_contains((string) $block, 'used in 1 place'), true);
    $say('used asset: MoveToRecycleBin refuses', $refused(fn () => app(MoveToRecycleBin::class)->handle($editor, $small)) !== null, true);
    $usage->delete();
    $say('unused asset: no block', $small->binUsageBlock($editor), null);
    $entry = app(MoveToRecycleBin::class)->handle($editor, $small);
    $say('bin: type and 30-day retention', [$entry->type, (int) round(now()->diffInDays($entry->purge_at, true))], ['media_asset', 30]);
    $say('bin: thumbnail url stored', str_contains((string) $entry->thumbnail, '/storage/media/'), true);
    $say('bin: soft deleted, files kept', [MediaAsset::withTrashed()->find($small->id)->trashed(), Storage::disk('public')->exists($sr->fallback)], [true, true]);
    app(RestoreFromRecycleBin::class)->handle($editor, $entry);
    $say('bin: restored', MediaAsset::query()->whereKey($small->id)->exists(), true);
    $entry = app(MoveToRecycleBin::class)->handle($editor, $small->refresh());
    $folder = dirname($sr->fallback);
    app(DeleteForever::class)->handle($admin, $entry);
    $say('forever: row and folder gone', [MediaAsset::withTrashed()->find($small->id), Storage::disk('public')->exists($folder)], [null, false]);
} finally {
    // Files are not rolled back with the database: delete every folder this run created.
    foreach (MediaAsset::withTrashed()->whereIn('id', $created)->get() as $asset) {
        $asset->forceDelete();
    }
    DB::rollBack();
    File::deleteDirectory($work);
}
