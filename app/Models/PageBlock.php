<?php

namespace App\Models;

use App\Support\Blocks\BlockType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DM-BLOCK: one block of a page (CMS-PB-001). `data` is the editor shape validated by the type's
 * `BlockSchema`; the public props come from `BlockResolver`. Media ids inside `data` are DM-MUSE
 * usages of the page with the field `blocks.{id}.{path}`.
 *
 * @property int $id
 * @property int $page_id
 * @property string $type
 * @property array<string, mixed> $data
 * @property bool $is_visible
 * @property string|null $anchor_id
 * @property string $background
 * @property string $spacing
 * @property bool $animate
 * @property int $sort_order
 */
#[Fillable(['page_id', 'type', 'data', 'is_visible', 'anchor_id', 'background', 'spacing', 'animate', 'sort_order'])]
class PageBlock extends Model
{
    /** CMS-PB-001. */
    public const array BACKGROUNDS = ['default', 'muted', 'brand', 'dark', 'gradient'];

    public const array SPACINGS = ['none', 'sm', 'md', 'lg'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'is_visible' => 'boolean', 'animate' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function blockType(): ?BlockType
    {
        return BlockType::tryFrom($this->type);
    }
}
