<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-REDIR and DM-404 (spec/seo.md). `redirects` is soft deleted (crud.md Recycle bin), so the
     * unique `source` also reserves the address of a rule that sits in the bin (DEL-08).
     * `source` of a prefix rule is stored without its `/*`.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 500)->unique();
            $table->boolean('is_prefix')->default(false);
            $table->string('target', 500)->nullable();
            $table->unsignedSmallInteger('code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'deleted_at']);
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->string('referrer', 500)->nullable();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->boolean('is_ignored')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('redirects');
    }
};
