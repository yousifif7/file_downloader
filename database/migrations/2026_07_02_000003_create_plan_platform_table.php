<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_platform', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->primary(['plan_id', 'platform_id']);
        });

        $freePlanId = DB::table('plans')->where('slug', 'free')->value('id');

        if ($freePlanId === null) {
            return;
        }

        $platformIds = DB::table('platforms')
            ->whereIn('slug', ['youtube', 'tiktok', 'twitter', 'direct'])
            ->pluck('id');

        foreach ($platformIds as $platformId) {
            DB::table('plan_platform')->insert([
                'plan_id' => $freePlanId,
                'platform_id' => $platformId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_platform');
    }
};
