<?php

namespace App\Support\Media;

/**
 * The conversions of one image as `GenerateConversions` wrote them (custom property `renditions`
 * of the spatie media row): paths on the media disk, in the shape of the `DemoMedia` manifest.
 *
 * @phpstan-type Rendition array{path: string, width: int}
 */
final readonly class Renditions
{
    /**
     * @param  list<Rendition>  $avif
     * @param  list<Rendition>  $webp
     * @param  list<Rendition>  $jpeg
     */
    public function __construct(
        public array $avif,
        public array $webp,
        public array $jpeg,
        public string $fallback,
        public string $og,
    ) {}

    /**
     * Reads the stored custom property; anything that is not the expected shape counts as "no conversions".
     */
    public static function fromStored(mixed $data): ?self
    {
        if (! is_array($data) || ! is_array($data['sources'] ?? null)) {
            return null;
        }

        if (! is_string($data['fallback'] ?? null) || ! is_string($data['og'] ?? null)) {
            return null;
        }

        return new self(
            self::items($data['sources']['avif'] ?? []),
            self::items($data['sources']['webp'] ?? []),
            self::items($data['sources']['jpeg'] ?? []),
            $data['fallback'],
            $data['og'],
        );
    }

    /**
     * @return array{sources: array{avif: list<Rendition>, webp: list<Rendition>, jpeg: list<Rendition>}, fallback: string, og: string}
     */
    public function toArray(): array
    {
        return [
            'sources' => ['avif' => $this->avif, 'webp' => $this->webp, 'jpeg' => $this->jpeg],
            'fallback' => $this->fallback,
            'og' => $this->og,
        ];
    }

    /**
     * Every file this set refers to.
     *
     * @return list<string>
     */
    public function paths(): array
    {
        return [
            ...array_column($this->avif, 'path'),
            ...array_column($this->webp, 'path'),
            ...array_column($this->jpeg, 'path'),
            $this->og,
        ];
    }

    /**
     * @return list<Rendition>
     */
    private static function items(mixed $items): array
    {
        $result = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item) && is_string($item['path'] ?? null) && is_int($item['width'] ?? null)) {
                $result[] = ['path' => $item['path'], 'width' => $item['width']];
            }
        }

        return $result;
    }
}
