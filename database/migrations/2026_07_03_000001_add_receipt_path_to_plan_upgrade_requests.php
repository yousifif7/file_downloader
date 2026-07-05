<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_upgrade_requests', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('payer_note');
        });
    }

    public function down(): void
    {
        Schema::table('plan_upgrade_requests', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }
};
