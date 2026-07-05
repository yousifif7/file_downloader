<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('billing_provider_plan_id')->nullable()->after('price_cents');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_status')->nullable()->after('quota_reset_at');
            $table->string('billing_provider')->nullable()->after('subscription_status');
            $table->string('billing_provider_customer_id')->nullable()->after('billing_provider');
            $table->string('billing_provider_subscription_id')->nullable()->after('billing_provider_customer_id');
            $table->timestamp('subscription_renews_at')->nullable()->after('billing_provider_subscription_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_renews_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'billing_provider',
                'billing_provider_customer_id',
                'billing_provider_subscription_id',
                'subscription_renews_at',
                'subscription_ends_at',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('billing_provider_plan_id');
        });
    }
};
