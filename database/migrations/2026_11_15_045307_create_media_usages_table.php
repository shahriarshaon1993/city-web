<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-MUSE: which entity field uses which asset. Written by the entities that pick media (13.x onwards);
     * the library reads it for the usage list and the delete guard (CMS-MED-007 / 008).
     */
    public function up(): void
    {
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('usable_type', 40); // morph alias
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 80);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'field', 'position'], 'media_usages_unique');
            $table->index(['usable_type', 'usable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};
