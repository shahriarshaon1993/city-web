<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The polymorphic tables every publishable entity shares (spec/cms.md): DM-SEO `seo_meta`,
     * DM-SLUGH `slug_histories` and DM-REV `revisions`. Owners are stored by morph alias (ARC-006).
     * `revisions` is only created here, so `projects.pending_revision_id` has its table; the
     * pending-revision behaviour arrives in 14.1.
     */
    public function up(): void
    {
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type', 40);
            $table->unsignedBigInteger('seoable_id');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 20)->default('index,follow');
            $table->string('og_title', 70)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->foreignId('og_image_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('twitter_card', 20)->default('summary_large_image');
            $table->string('focus_keyword', 80)->nullable();
            $table->boolean('schema_enabled')->default(true);
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });

        Schema::create('slug_histories', function (Blueprint $table) {
            $table->id();
            $table->string('sluggable_type', 40);
            $table->unsignedBigInteger('sluggable_id');
            $table->string('slug', 100);
            $table->timestamp('created_at')->nullable();

            $table->unique(['sluggable_type', 'slug']);
            $table->index(['sluggable_type', 'sluggable_id']);
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revisionable_type', 40);
            $table->unsignedBigInteger('revisionable_id');
            $table->string('kind', 10);
            $table->json('payload');
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['revisionable_type', 'revisionable_id', 'created_at'], 'revisions_owner_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('slug_histories');
        Schema::dropIfExists('seo_meta');
    }
};
