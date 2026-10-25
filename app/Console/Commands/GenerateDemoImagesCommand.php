<?php

namespace App\Console\Commands;

use App\Actions\Demo\GenerateDemoImages;
use App\Actions\Demo\GenerateDemoPlaceholders;
use App\Support\Demo\DemoMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:images
    {--placeholders : First draw labelled placeholders for missing demo photos}
    {--force : Re-encode every image, even unchanged ones}')]
#[Description('Convert the demo photos in storage/app/demo-src into public/demo (DEC-31)')]
final class GenerateDemoImagesCommand extends Command
{
    public function handle(GenerateDemoPlaceholders $placeholders, GenerateDemoImages $generate): int
    {
        // Large camera photos need far more than the default 128 MB in GD.
        ini_set('memory_limit', '1G');

        if ($this->option('placeholders')) {
            $written = $placeholders->handle();
            $this->components->info(count($written).' placeholder photo(s) written to '.DemoMedia::sourcePath().'.');
        }

        $this->components->info('AVIF: '.(function_exists('imageavif') ? 'yes' : 'no (imageavif missing; WebP + JPEG only)'));

        try {
            $result = $generate->handle(
                force: (bool) $this->option('force'),
                progress: fn (string $key, bool $converted) => $this->components->twoColumnDetail($key, $converted ? '<fg=green>converted</>' : '<fg=gray>unchanged</>'),
            );
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result['warnings'] as $warning) {
            $this->components->warn($warning);
        }

        $this->components->info(sprintf(
            '%d converted, %d unchanged, %d stale folder(s) removed. Manifest: %s',
            $result['converted'],
            $result['reused'],
            $result['removed'],
            DemoMedia::manifestPath(),
        ));

        return self::SUCCESS;
    }
}
