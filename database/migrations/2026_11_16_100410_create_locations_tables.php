<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-LOC `locations` (a two-level tree: city → area, FR-LOC-001) and its `location_nearby` pivot.
     * Cities and areas share one slug namespace. Addition to the spec: `segment_intros` holds the
     * optional area × segment intros of FR-LOC-004 (`{"residential": "<p>…</p>"}`).
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('type', 10);
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->longText('intro')->nullable();
            $table->json('facts')->nullable();
            $table->json('segment_intros')->nullable();
            $table->foreignId('hero_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('zoom')->default(14);
            $table->string('publish_status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'publish_status']);
            $table->index('deleted_at');
        });

        Schema::create('location_nearby', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('nearby_id')->constrained('locations')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['location_id', 'nearby_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_nearby');
        Schema::dropIfExists('locations');
    }
};
