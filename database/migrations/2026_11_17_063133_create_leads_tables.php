<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DM-LEAD, DM-LEADH and DM-SUBS (spec/leads.md). `reference` and `form_location` are additions
     * (DEC-86): the reference is what the thank-you page uses to count a lead once, the form
     * location says which button or panel the lead came from.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->char('reference', 26)->unique();
            $table->string('type', 20);
            $table->string('status', 24)->default('new');
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('whatsapp', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->char('country', 2)->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('project_unit_types')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('answers')->nullable();
            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->string('source_url', 500);
            $table->string('form_location', 40)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('referrer_host', 190)->nullable();
            $table->string('ga_client_id', 60)->nullable();
            $table->text('consent_text');
            $table->string('ip', 45)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->string('lost_reason', 200)->nullable();
            $table->string('won_note', 200)->nullable();
            $table->unsignedInteger('touches_count')->default(0);
            $table->timestamp('last_touched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['project_id', 'created_at']);
            $table->index(['phone', 'project_id', 'created_at']);
            $table->index('type');
            $table->index('utm_source');
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 16);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('status', 16)->default('pending');
            $table->char('token', 40)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->text('consent_text');
            $table->char('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
