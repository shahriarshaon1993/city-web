<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-VIEW: a named table state (filters, sort, columns, order, widths, per_page, density).
     */
    public function up(): void
    {
        Schema::create('saved_table_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('table_key', 40);
            $table->string('name', 60);
            $table->json('state');
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['table_key', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_table_views');
    }
};
