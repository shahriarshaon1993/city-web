<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DM-USER (spec/admin.md). The two-factor columns are left out on purpose:
 * the owner dropped 2FA in 10.1 (DECISIONS DEC-72). `avatar_asset_id` gets its
 * foreign key in 11.1, when `media_assets` exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 120)->change();
            $table->string('email', 190)->change();

            $table->string('phone', 20)->nullable()->after('email');
            $table->unsignedBigInteger('avatar_asset_id')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('theme', 10)->default('system')->after('is_active');
            $table->string('table_density', 12)->default('comfortable')->after('theme');
            $table->timestamp('last_login_at')->nullable()->after('table_density');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone', 'avatar_asset_id', 'is_active', 'theme', 'table_density', 'last_login_at',
            ]);
        });
    }
};
