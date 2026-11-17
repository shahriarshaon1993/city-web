<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 14.1 (CMS-PUB-002/003/006): areas get the same pending revision and optimistic lock projects
     * already have, both get `edited_by` for the "Changed by …" conflict message, and a pending
     * revision may carry its own go-live time (`publish_at`, scheduler every minute).
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('pending_revision_id')->nullable()->after('published_at')->constrained('revisions')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0)->after('pending_revision_id');
            $table->foreignId('edited_by')->nullable()->after('lock_version')->constrained('users')->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('edited_by')->nullable()->after('lock_version')->constrained('users')->nullOnDelete();
        });

        Schema::table('revisions', function (Blueprint $table) {
            $table->timestamp('publish_at')->nullable()->after('note');
            $table->index(['kind', 'publish_at'], 'revisions_kind_publish_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            $table->dropIndex('revisions_kind_publish_at_index');
            $table->dropColumn('publish_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by');
            $table->dropColumn('lock_version');
            $table->dropConstrainedForeignId('pending_revision_id');
        });
    }
};
