<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Queries\Site\NewsQuery;
use App\Support\News\NewsCopy;
use App\Support\Seo\SeoData;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * FR-NEWS-008 `/news/feed.xml`: RSS 2.0 with the 20 latest published posts. Cached for ten minutes; the
 * cache key follows the newest edit, so a publish or an unpublish shows at once.
 */
class NewsFeedController extends Controller
{
    private const int LIMIT = 20;

    public function __invoke(): Response
    {
        $posts = NewsQuery::query()->with('author')->limit(self::LIMIT)->get();
        $stamp = ($posts->isEmpty() ? '0' : (string) $posts->max('updated_at')).':'.$posts->count();

        $xml = Cache::remember('news:feed:'.md5($stamp), 600, fn (): string => $this->render(array_values($posts->all())));

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    /**
     * @param  list<Post>  $posts
     */
    private function render(array $posts): string
    {
        $copy = NewsCopy::labels();
        $site = SeoData::absoluteUrl('/');
        $feed = SeoData::absoluteUrl('/news/feed.xml');
        $latest = $posts === [] ? now() : ($posts[0]->published_at ?? now());

        $items = '';

        foreach ($posts as $post) {
            $url = SeoData::absoluteUrl($post->path());
            $items .= '<item>'
                .'<title>'.$this->text($post->title).'</title>'
                .'<link>'.$this->text($url).'</link>'
                .'<guid isPermaLink="true">'.$this->text($url).'</guid>'
                .'<pubDate>'.($post->published_at ?? $post->created_at)->toRfc2822String().'</pubDate>'
                .'<category>'.$this->text((string) $post->category?->name).'</category>'
                .'<description>'.$this->text($post->excerpt).'</description>'
                .'</item>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
            .'<title>'.$this->text($copy['rss_title']).'</title>'
            .'<link>'.$this->text(rtrim($site, '/').'/news').'</link>'
            .'<description>'.$this->text($copy['index_description']).'</description>'
            .'<language>en</language>'
            .'<lastBuildDate>'.$latest->toRfc2822String().'</lastBuildDate>'
            .'<atom:link href="'.$this->text($feed).'" rel="self" type="application/rss+xml"/>'
            .$items
            .'</channel></rss>';
    }

    private function text(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
