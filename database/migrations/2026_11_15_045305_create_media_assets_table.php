<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-MASSET: one library item owning one spatie file (`media` table, model_type `media_asset`).
     * Additions to the spec: `kind` (image / svg / video / document, for the type filter of CMS-MED-009)
     * and `conversions_status` (pending / ready / failed, so the library can show progress).
     */
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('kind', 12)->index();
            $table->string('title', 150);
            $table->string('alt', 125)->nullable();
            $table->string('caption', 255)->nullable();
            $table->string('credit', 120)->nullable();
            $table->boolean('is_decorative')->default(false);
            $table->unsignedTinyInteger('focal_x')->default(50);
            $table->unsignedTinyInteger('focal_y')->default(50);
            $table->char('dominant_color', 7)->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedSmallInteger('duration_s')->nullable();
            $table->string('conversions_status', 12)->default('pending');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('folder_id');
            $table->index('deleted_at');
            $table->fullText(['title', 'alt', 'caption']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
