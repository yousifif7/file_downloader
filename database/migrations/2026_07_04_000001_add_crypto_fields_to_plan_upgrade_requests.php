<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_upgrade_requests', function (Blueprint $table) {
            $table->string('payment_method')->default('bank_transfer')->after('status');
            $table->string('provider')->nullable()->after('payment_method');
            $table->string('provider_payment_id')->nullable()->after('provider');
            $table->string('invoice_url')->nullable()->after('provider_payment_id');

            $table->index('provider_payment_id');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('plan_upgrade_requests', function (Blueprint $table) {
            $table->dropIndex(['provider_payment_id']);
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['payment_method', 'provider', 'provider_payment_id', 'invoice_url']);
        });
    }
};
