<?php

namespace App\Support\RecycleBin;

use App\Models\Amenity;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry of the entities that live in the Recycle bin. A phase that adds an entity with
 * "Bin" in the crud.md matrix adds its model here (and to the morph map in AppServiceProvider).
 */
final class BinTypes
{
    /**
     * @var list<class-string<Model&Trashable>>
     */
    private const array TYPES = [
        User::class,
        MediaAsset::class,
        Redirect::class,
        Location::class,
        Amenity::class,
        Project::class,
    ];

    /**
     * @return list<class-string<Model&Trashable>>
     */
    public static function models(): array
    {
        return self::TYPES;
    }

    /**
     * @return class-string<Model&Trashable>|null
     */
    public static function modelFor(string $type): ?string
    {
        foreach (self::TYPES as $class) {
            if ($class::binType() === $type) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @return list<string> every permission that opens the bin for some type
     */
    public static function permissions(): array
    {
        return array_values(array_unique(array_map(fn (string $class): string => $class::binPermission(), self::TYPES)));
    }

    /**
     * @return list<string> the types this user may see, restore and delete forever
     */
    public static function typesFor(User $user): array
    {
        return array_column(self::options($user), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(User $user): array
    {
        $options = [];

        foreach (self::TYPES as $class) {
            if ($user->can($class::binPermission())) {
                $options[] = ['value' => $class::binType(), 'label' => $class::binTypeLabel()];
            }
        }

        return $options;
    }
}
