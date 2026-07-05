<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_url');
            $table->string('media_title')->nullable();
            $table->string('format')->nullable();
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
