<?php

namespace App\Models;

use App\Enums\FaqCategory;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-FAQ / FR-SOC-003: a question with a limited-rich-text answer. `show_on` lists the places it may
 * appear; `faqables` attaches it to single projects or areas. Delete = Recycle bin.
 *
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property FaqCategory $category
 * @property list<string> $show_on
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['question', 'answer', 'category', 'show_on', 'sort_order', 'is_published'])]
class Faq extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    public const int QUESTION_MAX = 160;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['category' => FaqCategory::class, 'show_on' => 'array', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return MorphToMany<Project, $this>
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'faqable')->withPivot('sort_order');
    }

    /**
     * @return MorphToMany<Location, $this>
     */
    public function locations(): MorphToMany
    {
        return $this->morphedByMany(Location::class, 'faqable')->withPivot('sort_order');
    }

    /**
     * @return MorphToMany<FacetPage, $this>
     */
    public function facetPages(): MorphToMany
    {
        return $this->morphedByMany(FacetPage::class, 'faqable')->withPivot('sort_order');
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopeShownOn(Builder $query, string $placement): Builder
    {
        return $query->whereJsonContains('show_on', $placement);
    }

    public static function binType(): string
    {
        return 'faq';
    }

    public static function binTypeLabel(): string
    {
        return 'FAQ';
    }

    public static function binPermission(): string
    {
        return 'social.manage';
    }

    public function binLabel(): string
    {
        return $this->question;
    }

    public function binUrl(): ?string
    {
        return route('admin.faqs.edit', $this);
    }
}
