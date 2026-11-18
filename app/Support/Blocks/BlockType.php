<?php

namespace App\Support\Blocks;

use App\Support\Blocks\Schemas\AwardsSchema;
use App\Support\Blocks\Schemas\CtaBandSchema;
use App\Support\Blocks\Schemas\FaqSchema;
use App\Support\Blocks\Schemas\FeatureCardsSchema;
use App\Support\Blocks\Schemas\FeaturedProjectsSchema;
use App\Support\Blocks\Schemas\FormSchema;
use App\Support\Blocks\Schemas\GallerySchema;
use App\Support\Blocks\Schemas\HeroSchema;
use App\Support\Blocks\Schemas\ImageTextSchema;
use App\Support\Blocks\Schemas\IntroStatementSchema;
use App\Support\Blocks\Schemas\LatestNewsSchema;
use App\Support\Blocks\Schemas\LocationsGridSchema;
use App\Support\Blocks\Schemas\OfficesSchema;
use App\Support\Blocks\Schemas\PageHeaderSchema;
use App\Support\Blocks\Schemas\ParallaxBandSchema;
use App\Support\Blocks\Schemas\PartnersMarqueeSchema;
use App\Support\Blocks\Schemas\ProcessStepsSchema;
use App\Support\Blocks\Schemas\ProgressHighlightSchema;
use App\Support\Blocks\Schemas\ProjectGridSchema;
use App\Support\Blocks\Schemas\QuoteSchema;
use App\Support\Blocks\Schemas\RichTextSchema;
use App\Support\Blocks\Schemas\SegmentTilesSchema;
use App\Support\Blocks\Schemas\StatsCountersSchema;
use App\Support\Blocks\Schemas\TabbedPillarsSchema;
use App\Support\Blocks\Schemas\TeamGridSchema;
use App\Support\Blocks\Schemas\TestimonialsSchema;
use App\Support\Blocks\Schemas\TimelineSchema;
use App\Support\Blocks\Schemas\VideoSchema;

/**
 * The 28 block types of the catalogue (spec/page-builder.md §8.1). The value is `page_blocks.type`
 * and the `type` the public `BlockRenderer` switches on; each type has one `BlockSchema`.
 */
enum BlockType: string
{
    case Hero = 'hero';
    case PageHeader = 'page_header';
    case IntroStatement = 'intro_statement';
    case FeaturedProjects = 'featured_projects';
    case ProjectGrid = 'project_grid';
    case SegmentTiles = 'segment_tiles';
    case StatsCounters = 'stats_counters';
    case ProgressHighlight = 'progress_highlight';
    case Testimonials = 'testimonials';
    case PartnersMarquee = 'partners_marquee';
    case LatestNews = 'latest_news';
    case CtaBand = 'cta_band';
    case RichText = 'rich_text';
    case ImageText = 'image_text';
    case FeatureCards = 'feature_cards';
    case ProcessSteps = 'process_steps';
    case Timeline = 'timeline';
    case TabbedPillars = 'tabbed_pillars';
    case TeamGrid = 'team_grid';
    case Faq = 'faq';
    case Gallery = 'gallery';
    case Video = 'video';
    case Awards = 'awards';
    case LocationsGrid = 'locations_grid';
    case Offices = 'offices';
    case Form = 'form';
    case Quote = 'quote';
    case ParallaxBand = 'parallax_band';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::PageHeader => 'Page header',
            self::IntroStatement => 'Intro statement',
            self::FeaturedProjects => 'Featured projects',
            self::ProjectGrid => 'Project grid',
            self::SegmentTiles => 'Segment tiles',
            self::StatsCounters => 'Stats counters',
            self::ProgressHighlight => 'Construction progress',
            self::Testimonials => 'Testimonials',
            self::PartnersMarquee => 'Partners strip',
            self::LatestNews => 'Latest news',
            self::CtaBand => 'Call-to-action band',
            self::RichText => 'Rich text',
            self::ImageText => 'Image and text',
            self::FeatureCards => 'Feature columns',
            self::ProcessSteps => 'Process steps',
            self::Timeline => 'Timeline',
            self::TabbedPillars => 'Tabbed pillars',
            self::TeamGrid => 'Team grid',
            self::Faq => 'FAQ',
            self::Gallery => 'Gallery',
            self::Video => 'Video',
            self::Awards => 'Awards',
            self::LocationsGrid => 'Locations grid',
            self::Offices => 'Offices',
            self::Form => 'Enquiry form',
            self::Quote => 'Quote',
            self::ParallaxBand => 'Photo band',
        };
    }

    /** CMS-PB-004: only these can open a page, because their heading is the page H1. */
    public function canOpenPage(): bool
    {
        return $this === self::Hero || $this === self::PageHeader;
    }

    public function schema(): BlockSchema
    {
        return app($this->schemaClass());
    }

    /**
     * @return class-string<BlockSchema>
     */
    private function schemaClass(): string
    {
        return match ($this) {
            self::Hero => HeroSchema::class,
            self::PageHeader => PageHeaderSchema::class,
            self::IntroStatement => IntroStatementSchema::class,
            self::FeaturedProjects => FeaturedProjectsSchema::class,
            self::ProjectGrid => ProjectGridSchema::class,
            self::SegmentTiles => SegmentTilesSchema::class,
            self::StatsCounters => StatsCountersSchema::class,
            self::ProgressHighlight => ProgressHighlightSchema::class,
            self::Testimonials => TestimonialsSchema::class,
            self::PartnersMarquee => PartnersMarqueeSchema::class,
            self::LatestNews => LatestNewsSchema::class,
            self::CtaBand => CtaBandSchema::class,
            self::RichText => RichTextSchema::class,
            self::ImageText => ImageTextSchema::class,
            self::FeatureCards => FeatureCardsSchema::class,
            self::ProcessSteps => ProcessStepsSchema::class,
            self::Timeline => TimelineSchema::class,
            self::TabbedPillars => TabbedPillarsSchema::class,
            self::TeamGrid => TeamGridSchema::class,
            self::Faq => FaqSchema::class,
            self::Gallery => GallerySchema::class,
            self::Video => VideoSchema::class,
            self::Awards => AwardsSchema::class,
            self::LocationsGrid => LocationsGridSchema::class,
            self::Offices => OfficesSchema::class,
            self::Form => FormSchema::class,
            self::Quote => QuoteSchema::class,
            self::ParallaxBand => ParallaxBandSchema::class,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
