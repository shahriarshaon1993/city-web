<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use Illuminate\Support\Facades\File;

/**
 * Writes the PG-04 demo files (owner, 4.3): one-page placeholder PDFs for
 * the DM-PDOC kinds into storage/app/demo-src/documents/ and the WebVTT
 * captions of the demo project film into storage/app/demo-src/video/ (the
 * film itself is dropped in by the owner as `project.mp4`). Existing files
 * are never overwritten. The PDF is written by hand (Helvetica, A4), so no
 * package is needed; every file says it is a placeholder.
 */
final class GenerateDemoDocuments
{
    /** File name => title and lines of the placeholder page. */
    private const array DOCUMENTS = [
        'brochure' => ['Project brochure', ['Sample brochure - placeholder.', 'The client\'s brochure replaces this file before launch.']],
        'price-list' => ['Price list', ['Sample price list - placeholder.', 'Prices are confirmed by the sales team at booking.']],
        'specification' => ['Specification', ['Sample specification sheet - placeholder.', 'Structure, finishes and fittings are listed per project.']],
        'plot-layout' => ['Plot layout', ['Sample plot layout - placeholder.', 'Plot numbers, sizes and road widths are listed per project.']],
    ];

    private const string CAPTIONS = <<<'VTT'
        WEBVTT

        00:00:00.000 --> 00:00:04.000
        [Soft piano music]

        00:00:04.000 --> 00:00:09.000
        Every home here starts with light, air and a view of green.

        00:00:09.000 --> 00:00:15.000
        Placeholder captions: the project film replaces this demo.
        VTT;

    /**
     * @return list<string> files written, relative to storage/app/demo-src
     */
    public function handle(): array
    {
        $written = [];
        $dir = DemoMedia::sourcePath('documents');
        File::ensureDirectoryExists($dir);

        foreach (self::DOCUMENTS as $name => [$title, $lines]) {
            if (! File::exists("{$dir}/{$name}.pdf")) {
                File::put("{$dir}/{$name}.pdf", $this->pdf($title, $lines));
                $written[] = "documents/{$name}.pdf";
            }
        }

        $videoDir = DemoMedia::sourcePath(CopyDemoVideos::GROUP);
        File::ensureDirectoryExists($videoDir);

        if (! File::exists("{$videoDir}/project.vtt")) {
            File::put("{$videoDir}/project.vtt", self::CAPTIONS."\n");
            $written[] = 'video/project.vtt';
        }

        return $written;
    }

    /**
     * A one-page A4 PDF: brand rule, title and body lines (ASCII only).
     *
     * @param  list<string>  $lines
     */
    private function pdf(string $title, array $lines): string
    {
        $text = 'BT /F1 10 Tf 0.008 0.463 0.522 rg 56 770 Td (GEMCON CITY - DEMO DOCUMENT) Tj ET '
            .'0.008 0.463 0.522 rg 56 752 483 1 re f '
            .'BT /F1 30 Tf 0.11 0.098 0.09 rg 56 690 Td ('.$this->escape($title).') Tj ET';

        foreach ($lines as $index => $line) {
            $text .= ' BT /F1 12 Tf 0.36 0.34 0.33 rg 56 '.(640 - $index * 20).' Td ('.$this->escape($line).') Tj ET';
        }

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($text)." >>\nstream\n{$text}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    private function escape(string $text): string
    {
        return strtr($text, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }
}
