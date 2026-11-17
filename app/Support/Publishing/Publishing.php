<?php

namespace App\Support\Publishing;

use App\Models\Contracts\PublishableEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

/**
 * The publishable entities of the workflow (CMS-PUB), by morph alias. A later entity (pages, posts,
 * job posts) joins by adding a `PublishingDriver` here; the Actions, the scheduler and the preview
 * route then work for it unchanged.
 */
final class Publishing
{
    /** @var array<string, class-string<PublishingDriver>> */
    private const array DRIVERS = [
        'project' => ProjectPublishing::class,
        'location' => AreaPublishing::class,
    ];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::DRIVERS);
    }

    public static function has(string $type): bool
    {
        return isset(self::DRIVERS[$type]);
    }

    public static function driver(string $type): PublishingDriver
    {
        return app(self::DRIVERS[$type]);
    }

    public static function for(Model $entity): PublishingDriver
    {
        return self::driver($entity->getMorphClass());
    }

    /**
     * @return class-string<Model>
     */
    public static function modelClass(string $type): string
    {
        return Relation::getMorphedModel($type) ?? throw new LogicException("No model is registered for [{$type}].");
    }

    /**
     * The saved entity of a type, or a 404 (also for a soft-deleted one and for a model that is not part of
     * the workflow).
     */
    public static function find(string $type, int $id): Model&PublishableEntity
    {
        abort_unless(self::has($type), 404);

        $entity = self::modelClass($type)::query()->whereKey($id)->firstOrFail();

        if (! $entity instanceof PublishableEntity) {
            abort(404);
        }

        return $entity;
    }
}
