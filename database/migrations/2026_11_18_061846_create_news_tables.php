<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-PCAT `post_categories`, DM-TAG `tags` + `taggables`, DM-POST `posts` + `post_project`
     * (spec/news.md). Posts carry the shared publishing columns (CMS-PUB, DEC-85). `author_team_member_id`
     * has no foreign key until the team table exists (16.4).
     */
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // The default collation is case-insensitive, so "NRB" and "nrb" are one tag (FR-NEWS-003).
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 60)->unique();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('taggable_type', 40);
            $table->unsignedBigInteger('taggable_id');
            $table->primary(['tag_id', 'taggable_type', 'taggable_id']);
            $table->index(['taggable_type', 'taggable_id']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 110);
            $table->string('slug', 110)->unique();
            $table->string('excerpt', 200)->default('');
            $table->longText('body')->nullable();
            $table->foreignId('cover_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('category_id')->constrained('post_categories')->restrictOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('author_team_member_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedTinyInteger('read_minutes')->default(1);
            $table->string('publish_status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('pending_revision_id')->nullable()->constrained('revisions')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publish_status', 'published_at']);
            $table->index('deleted_at');
            $table->fullText(['title', 'excerpt']);
            $table->fullText('body');
        });

        Schema::create('post_project', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['post_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_project');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('post_categories');
    }
};
