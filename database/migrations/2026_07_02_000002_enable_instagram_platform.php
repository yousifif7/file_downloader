<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platforms')
            ->where('slug', 'instagram')
            ->update(['is_enabled' => true]);
    }

    public function down(): void
    {
        DB::table('platforms')
            ->where('slug', 'instagram')
            ->update(['is_enabled' => false]);
    }
};
