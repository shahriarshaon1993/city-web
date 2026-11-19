<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-JOB `job_posts` and DM-APP `job_applications` (spec/careers.md). Both are Recycle bin entities
     * (crud.md DEL-01). Additions to the spec (DEC-94): `job_posts.summary` (the one-sentence lead of
     * PG-15/16), `job_applications.cv_name` (the file name offered on download), `review_reason` (why an
     * application is flagged, FR-LEAD-003) and `updated_at` (NFR-PRIV-04: CVs go 12 months after the last update).
     */
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 120)->unique();
            $table->string('department', 60);
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('employment_type', 12);
            $table->string('experience', 80)->nullable();
            $table->string('salary_text', 80)->nullable();
            $table->unsignedBigInteger('salary_min_bdt')->nullable();
            $table->unsignedBigInteger('salary_max_bdt')->nullable();
            $table->string('summary', 200);
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->longText('benefits')->nullable();
            $table->date('deadline');
            $table->string('status', 8)->default('draft');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deadline']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->nullable()->constrained('job_posts')->nullOnDelete();
            $table->string('reference', 26)->unique();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email', 190);
            $table->string('current_position', 120)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('expected_salary', 40)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('cv_path', 255);
            $table->string('cv_name', 120);
            $table->unsignedInteger('cv_size')->default(0);
            $table->text('cover_letter')->nullable();
            $table->string('status', 12)->default('new');
            $table->string('review_reason', 24)->nullable();
            $table->text('notes')->nullable();
            $table->text('consent_text');
            $table->string('ip_hash', 64)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('job_post_id');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posts');
    }
};
