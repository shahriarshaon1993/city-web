<?php

namespace App\Support\News;

use App\Support\Seo\SeoData;

/**
 * Labels and headings of PG-12…14 (CMS-000a: copy comes from props, not from Vue) and the share links of
 * CMP-SHARE-01. Not content: nothing here is edited in the admin.
 */
final class NewsCopy
{
    /** The byline of a post with no author chosen. */
    public const string DEFAULT_AUTHOR = 'Gemcon City editorial team';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'index_title' => 'News and insights',
            'index_intro' => 'Construction updates, buying guides and announcements from Gemcon City Limited.',
            'index_description' => 'Construction progress, buying guides, events and announcements from Gemcon City Limited, a Bangladeshi real-estate developer.',
            'all_label' => 'All',
            'categories_label' => 'Categories',
            'tag_title' => 'Tagged “{tag}”',
            'tag_intro' => 'Articles tagged {tag}.',
            'tag_description' => 'Gemcon City articles tagged {tag}.',
            'empty_title' => 'No articles here yet',
            'empty_body' => 'Nothing has been published in this section. Read all of our news instead.',
            'empty_action' => 'All news',
            'by_label' => 'By',
            'projects_heading' => 'Projects in this article',
            'tags_label' => 'Tagged',
            'related_heading' => 'Keep reading',
            'related_view_all' => 'All news',
            'newsletter_heading' => 'Construction updates and buying guides, once a month',
            'newsletter_text' => 'New launches, progress photos and plain-language guides. No spam, unsubscribe any time.',
            'share_heading' => 'Share this article',
            'share_copy_label' => 'Copy link',
            'share_copied' => 'Link copied',
            'share_failed' => 'Could not copy the link. Copy it from the address bar instead.',
            'watch_video' => 'Watch the video on YouTube',
            'rss_title' => 'Gemcon City news',
        ];
    }

    /**
     * CMP-SHARE-01: share-intent URLs on the three networks (no SDKs).
     *
     * @return array<string, mixed>
     */
    public static function share(string $slug, string $title): array
    {
        $copy = self::labels();
        $url = SeoData::absoluteUrl('/news/'.$slug);
        $encodedUrl = rawurlencode($url);

        return [
            'url' => $url,
            'title' => $title,
            'heading' => $copy['share_heading'],
            'copy_label' => $copy['share_copy_label'],
            'copied_message' => $copy['share_copied'],
            'failed_message' => $copy['share_failed'],
            'links' => [
                ['network' => 'facebook', 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl],
                ['network' => 'linkedin', 'label' => 'LinkedIn', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$encodedUrl],
                ['network' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/?text='.rawurlencode($title.' '.$url)],
            ],
        ];
    }
}
