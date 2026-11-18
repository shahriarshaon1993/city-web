<?php

namespace App\Support\Publishing;

use App\Actions\Pages\SavePage;
use App\Models\Page;
use App\Models\User;
use App\Queries\Admin\PageEditor;
use App\Support\Pages\PageChecklist;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Pages in the publishing workflow (CMS-PB, CMS-PUB): the editor payload is `PageEditor::form`,
 * applying it is `SavePage`, the publish rules are `PageChecklist`.
 */
final class PagePublishing implements PublishingDriver
{
    public function __construct(
        private readonly PageHydrator $hydrator,
        private readonly SavePage $savePage,
        private readonly PageChecklist $checklist,
    ) {}

    public function type(): string
    {
        return 'page';
    }

    public function logName(): string
    {
        return 'pages';
    }

    public function noun(): string
    {
        return 'page';
    }

    public function label(Model $entity): string
    {
        return $this->page($entity)->title;
    }

    public function publicPath(Model $entity): string
    {
        return $this->page($entity)->path();
    }

    public function snapshot(Model $entity): array
    {
        return PageEditor::form($this->page($entity));
    }

    public function hydrate(Model $entity, array $payload, bool $preview = false): Page
    {
        return $this->hydrator->handle($this->page($entity), $payload);
    }

    public function apply(?User $actor, Model $entity, array $payload, bool $log = true): Page
    {
        return $this->savePage->handle($actor, $payload, $this->page($entity), $log);
    }

    public function checklist(Model $entity): array
    {
        return $this->checklist->items($this->page($entity));
    }

    public function fieldLabels(): array
    {
        return ['title' => 'Title', 'slug' => 'URL', 'template' => 'Template', 'last_reviewed_at' => 'Last updated', 'blocks' => 'Blocks', 'seo' => 'SEO'];
    }

    public function htmlFields(): array
    {
        return [];
    }

    private function page(Model $entity): Page
    {
        return $entity instanceof Page ? $entity : throw new InvalidArgumentException('Expected a page.');
    }
}
