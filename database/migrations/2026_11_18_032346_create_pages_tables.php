<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-PAGE `pages` and DM-BLOCK `page_blocks` (spec/page-builder.md). A page is an ordered list of
     * blocks (CMS-PB-001); `template` is `builder` or `legal`. System pages (home, about, …) cannot be
     * deleted or re-slugged. Publishing columns are the shared workflow ones (CMS-PUB, DEC-85).
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 80)->unique();
            $table->string('template', 12)->default('builder');
            $table->boolean('is_system')->default(false);
            $table->string('publish_status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('pending_revision_id')->nullable()->constrained('revisions')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['template', 'publish_status']);
            $table->index('deleted_at');
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('type', 30);
            $table->json('data');
            $table->boolean('is_visible')->default(true);
            $table->string('anchor_id', 42)->nullable();
            $table->string('background', 10)->default('default');
            $table->string('spacing', 4)->default('md');
            $table->boolean('animate')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_id', 'sort_order']);
            $table->unique(['page_id', 'anchor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('pages');
    }
};
