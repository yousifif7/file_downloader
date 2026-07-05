<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->foreignId('plan_id')->nullable()->after('is_admin')->constrained()->nullOnDelete();
            $table->unsignedInteger('downloads_this_month')->default(0)->after('plan_id');
            $table->date('quota_reset_at')->nullable()->after('downloads_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['is_admin', 'downloads_this_month', 'quota_reset_at']);
        });
    }
};
