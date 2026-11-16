<?php

namespace App\Models;

use App\Enums\FacetKind;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * DM-FACET: the title (H1) and intro of one facet landing page (`/projects/type/residential`,
 * `/projects/status/ongoing`). The rows are fixed — one per segment and per lifecycle value — and
 * only their text changes; the meta description lives in the SEO row.
 *
 * @property int $id
 * @property FacetKind $kind
 * @property string $value
 * @property string $title
 * @property string|null $intro
 * @property int $sort_order
 */
#[Fillable(['kind', 'value', 'title', 'intro', 'sort_order'])]
class FacetPage extends Model
{
    use HasSeo;

    public const int TITLE_MAX = 80;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => FacetKind::class, 'sort_order' => 'integer'];
    }
}
