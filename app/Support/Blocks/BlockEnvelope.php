<?php

namespace App\Support\Blocks;

/**
 * The DM-BLOCK envelope (CMS-PB-001) for a block that a controller builds itself instead of loading it
 * from a page: the closing CTA band of the project pages, the contact page's header and offices. The
 * hero is edge to edge (`none`).
 */
final class BlockEnvelope
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function make(int $id, string $type, array $data, string $background = 'default', ?string $spacing = null): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'is_visible' => true,
            'anchor_id' => null,
            'background' => $background,
            'spacing' => $spacing ?? ($type === 'hero' ? 'none' : 'md'),
            'animate' => true,
            'data' => $data,
        ];
    }
}
