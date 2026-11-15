<?php

/**
 * Phase 11.1 check, real queue: uploads a 6000×4000 JPEG (committed), then — after you run the
 * worker — shows what the `media` queue produced and deletes everything again.
 *
 *   1. php -d memory_limit=1G artisan tinker docs/checks/11.1-media-queue.php     (uploads, prints "pending")
 *   2. php artisan queue:work --queue=media --stop-when-empty                     (converts)
 *   3. php artisan tinker docs/checks/11.1-media-queue.php                        (prints the result, cleans up)
 */

use App\Actions\Media\UploadMedia;
use App\Http\Resources\Media\MediaImageResource;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

$asset = MediaAsset::query()->where('title', 'queue-check')->latest('id')->first();

if ($asset === null) {
    $path = storage_path('app/queue-check.jpg');
    $image = imagecreatetruecolor(6000, 4000);
    for ($y = 0; $y < 4000; $y += 8) {
        imagefilledrectangle($image, 0, $y, 6000, $y + 8, imagecolorallocate($image, 40 + (int) ($y / 4000 * 120), 90, 160 - (int) ($y / 4000 * 80)));
    }
    imagejpeg($image, $path, 80);

    $editor = User::role(SystemRole::ContentEditor->value)->where('is_active', true)->orderBy('id')->firstOrFail();
    $asset = app(UploadMedia::class)->handle($editor, new UploadedFile($path, 'queue-check.jpg', null, null, true));
    File::delete($path);

    echo "uploaded #{$asset->id}, status: {$asset->conversions_status}\n";
    echo 'jobs waiting on the media queue: '.DB::table('jobs')->where('queue', 'media')->count()." (expect 1)\n";
    echo "now run: php artisan queue:work --queue=media --stop-when-empty\n";

    return;
}

$asset->refresh();
echo "item #{$asset->id}, status: {$asset->conversions_status} (expect ready)\n";
echo 'size: '.$asset->width.' × '.$asset->height."\n";
echo 'dominant colour: '.$asset->dominant_color."\n";
echo 'failed jobs: '.DB::table('failed_jobs')->count()."\n";
echo "files:\n";
foreach (File::files(dirname($asset->file()->getPath())) as $file) {
    echo '  '.$file->getFilename().'  '.number_format($file->getSize() / 1024, 0).' KB'."\n";
}
echo "MediaImage JSON:\n".json_encode((new MediaImageResource($asset))->resolve(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

$asset->forceDelete();
echo "cleaned up (row and folder deleted)\n";
