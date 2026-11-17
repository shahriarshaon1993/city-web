<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-WEBH (spec/leads.md): one row per webhook delivery, updated on every attempt, pruned after
     * 30 days (FR-LEAD-012). `lead_id` is nulled when a lead is erased so the log survives without PII.
     */
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event', 30);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('response_excerpt', 500)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
