<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ADM-IO-001 / ADM-TBL-012: one row per import wizard run and per queued export. Both only point
     * at files on the private disk (`imports/{id}`, `exports/{id}`); the files are removed by
     * `transfers:purge`, so a row never keeps people's data for long.
     */
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('original_name', 190);
            $table->string('extension', 5);
            $table->string('status', 12)->default('uploaded');
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('error_path', 255)->nullable();
            $table->string('message', 300)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('export_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('table_key', 40);
            $table->string('format', 5);
            $table->json('columns');
            $table->json('query');
            $table->string('status', 12)->default('queued');
            $table->unsignedInteger('row_count')->nullable();
            $table->string('filename', 190);
            $table->string('path', 255)->nullable();
            $table->string('message', 300)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_runs');
        Schema::dropIfExists('import_runs');
    }
};
