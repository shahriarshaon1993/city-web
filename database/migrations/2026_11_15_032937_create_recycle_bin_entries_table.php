<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DEL-04: one row per item in the Recycle bin, across every bin type. The item itself stays in
     * its own table (soft deleted); this index holds what the bin screen shows and when it purges.
     */
    public function up(): void
    {
        Schema::create('recycle_bin_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40); // morph alias of the model, e.g. "user"
            $table->unsignedBigInteger('trashable_id');
            $table->string('label', 190);
            $table->string('thumbnail', 255)->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamp('purge_at')->nullable(); // null = never auto-deleted (DEL-06)
            $table->timestamps();

            $table->unique(['type', 'trashable_id']);
            $table->index('deleted_at');
            $table->index('purge_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycle_bin_entries');
    }
};
