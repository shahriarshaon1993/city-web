<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-USER `avatar_asset_id` → `media_assets` (promised for 11.1, added with the picker in 11.2).
     * The avatar also writes a DM-MUSE row, so a used picture cannot be deleted; nullOnDelete only
     * covers a forced cleanup.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('avatar_asset_id')->references('id')->on('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['avatar_asset_id']);
        });
    }
};
