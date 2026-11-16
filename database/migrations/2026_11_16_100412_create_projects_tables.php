<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-PROJ, DM-UNIT, DM-AMEN, DM-PROG, DM-NEAR, DM-PDOC and DM-SIMILAR (spec/projects.md).
     * Addition to the spec: `projects.gallery` is a JSON list `[{asset_id, category, caption}]` (≤ 60,
     * FR-PROJ "gallery … each with alt + caption + category"), like `highlights`. The assets of every
     * media field are also written to `media_usages` (DM-MUSE). Money is whole taka (BIGINT).
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 90);
            $table->string('slug', 80)->unique();
            $table->string('tagline', 120)->nullable();
            $table->string('segment', 20);
            $table->string('lifecycle', 20);
            $table->string('booking_state', 20)->default('none');
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('land_area_katha', 8, 2)->nullable();
            $table->string('storeys', 40)->nullable();
            $table->unsignedSmallInteger('total_units')->nullable();
            $table->unsignedSmallInteger('car_parks')->nullable();
            $table->unsignedTinyInteger('units_per_floor')->nullable();
            $table->unsignedTinyInteger('lifts')->nullable();
            $table->unsignedTinyInteger('basements')->nullable();
            $table->unsignedSmallInteger('road_width_ft')->nullable();
            $table->string('facing', 40)->nullable();
            $table->string('orientation', 40)->nullable();
            $table->date('launch_date')->nullable();
            $table->date('handover_date')->nullable();
            $table->string('rajuk_approval_no', 80)->nullable();
            $table->string('architect', 150)->nullable();
            $table->string('structural_engineer', 150)->nullable();
            $table->string('contractor', 150)->nullable();
            $table->json('custom_facts')->nullable();
            $table->longText('overview')->nullable();
            $table->longText('design_narrative')->nullable();
            $table->json('highlights')->nullable();
            $table->json('gallery')->nullable();
            $table->foreignId('cover_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('video_type', 10)->default('none');
            $table->string('video_url', 255)->nullable();
            $table->foreignId('video_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('video_poster_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('video_captions_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('virtual_tour_url', 255)->nullable();
            $table->boolean('show_price')->default(false);
            $table->unsignedBigInteger('price_from_bdt')->nullable();
            $table->boolean('price_from_manual')->default(false);
            $table->string('price_note', 80)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->smallInteger('featured_order')->nullable();
            $table->boolean('brochure_gated')->default(true);
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('whatsapp_override', 20)->nullable();
            $table->unsignedInteger('size_min_sft')->nullable();
            $table->unsignedInteger('size_max_sft')->nullable();
            $table->unsignedTinyInteger('beds_min')->nullable();
            $table->unsignedTinyInteger('beds_max')->nullable();
            $table->unsignedTinyInteger('baths_min')->nullable();
            $table->unsignedTinyInteger('baths_max')->nullable();
            $table->string('publish_status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('pending_revision_id')->nullable()->constrained('revisions')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('archived_redirect_to', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publish_status', 'segment', 'lifecycle']);
            $table->index(['location_id', 'publish_status']);
            $table->index(['is_featured', 'featured_order']);
            $table->index('size_min_sft');
            $table->index('beds_min');
            $table->index('price_from_bdt');
            $table->index('deleted_at');
            $table->fullText(['name', 'tagline']);
            $table->fullText('overview');
        });

        Schema::create('project_unit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('size_sft');
            $table->unsignedInteger('size_sft_max')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('balconies')->nullable();
            $table->string('facing', 40)->nullable();
            $table->string('floor_range', 60)->nullable();
            $table->foreignId('floor_plan_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('plan_description', 300)->nullable();
            $table->foreignId('plan_3d_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedBigInteger('price_bdt')->nullable();
            $table->boolean('show_price')->default(false);
            $table->string('availability', 12)->default('available');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 80)->unique();
            $table->string('icon', 40);
            $table->string('category', 20);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('amenity_project', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->restrictOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['project_id', 'amenity_id']);
        });

        Schema::create('project_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('reported_on');
            $table->unsignedTinyInteger('percent');
            $table->string('stage', 12);
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'reported_on']);
        });

        Schema::create('project_nearby_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 20);
            $table->unsignedSmallInteger('minutes');
            $table->string('mode', 5);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained('media_assets')->restrictOnDelete();
            $table->string('title', 100);
            $table->string('kind', 20);
            $table->boolean('is_gated')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_similar', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('similar_id')->constrained('projects')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['project_id', 'similar_id']);
        });

        Schema::create('facet_pages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10);
            $table->string('value', 20);
            $table->string('title', 80);
            $table->longText('intro')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['kind', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facet_pages');
        Schema::dropIfExists('project_similar');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_nearby_places');
        Schema::dropIfExists('project_progress_updates');
        Schema::dropIfExists('amenity_project');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('project_unit_types');
        Schema::dropIfExists('projects');
    }
};
