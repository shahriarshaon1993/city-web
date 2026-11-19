<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Database\Seeders\Data\DemoNews;
use Illuminate\Database\Seeder;

/**
 * FR-NEWS-002: the default news categories — News, Press releases, Buying guides, Construction
 * updates, Events — in every environment. An existing category (also one in the Recycle bin) is
 * never changed or added twice.
 */
class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoNews::categories() as $position => $category) {
            PostCategory::withTrashed()->firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'description' => $category['description'], 'sort_order' => $position + 1],
            );
        }
    }
}
