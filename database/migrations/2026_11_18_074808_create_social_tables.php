<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-TEST `testimonials`, DM-PART `partners`, DM-FAQ `faqs` + `faqables`, DM-TEAM `team_members`,
     * DM-OFF `offices` (spec/social.md). All five are Recycle bin entities (crud.md DEL-01). The
     * news `author_team_member_id` column, created without a key in 16.3, gets its foreign key here.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('role_title', 100);
            $table->string('group', 12);
            $table->foreignId('photo_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('bio', 400)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('email', 190)->nullable();
            $table->boolean('show_email')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group', 'sort_order']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->text('quote');
            $table->string('person_name', 100);
            $table->string('designation', 120)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('photo_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('is_nrb')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('logo_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('url', 255)->nullable();
            $table->string('category', 20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 160);
            $table->text('answer');
            $table->string('category', 20);
            $table->json('show_on');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'sort_order']);
        });

        // A question attached to one project or area (FR-SOC-003); `sort_order` is the order on that page.
        Schema::create('faqables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->constrained('faqs')->cascadeOnDelete();
            $table->string('faqable_type', 40);
            $table->unsignedBigInteger('faqable_id');
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['faq_id', 'faqable_type', 'faqable_id']);
            $table->index(['faqable_type', 'faqable_id', 'sort_order']);
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('address', 255);
            $table->string('city', 60);
            $table->string('postal_code', 10)->nullable();
            $table->json('phones');
            $table->string('email', 190)->nullable();
            $table->json('hours');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('author_team_member_id')->references('id')->on('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['author_team_member_id']);
        });

        Schema::dropIfExists('offices');
        Schema::dropIfExists('faqables');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('team_members');
    }
};
