<?php

use App\Support\Menus\MenuKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-MENU. The seven menus of CMS-NAV-001 always exist (their keys are fixed in the theme), so the
     * migration creates them; the items are seeded by `MenuSeeder` and edited in the admin.
     * `linkable_type` holds the morph alias of an entity link, `linkable_id` its ID (CMS-NAV-004).
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->string('name', 40);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 30);
            $table->string('link_type', 12);
            $table->string('linkable_type', 30)->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('anchor', 42)->nullable();
            $table->boolean('new_tab')->default(false);
            $table->string('icon', 40)->nullable();
            $table->string('description', 80)->nullable();
            $table->boolean('is_mega')->default(false);
            $table->json('mega_columns')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });

        $now = now();

        DB::table('menus')->insert(array_map(
            fn (MenuKey $key): array => ['key' => $key->value, 'name' => $key->defaultName(), 'created_at' => $now, 'updated_at' => $now],
            MenuKey::cases(),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
