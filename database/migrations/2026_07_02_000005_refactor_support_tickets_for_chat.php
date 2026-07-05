<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_messages')) {
            Schema::create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_staff')->default(false);
                $table->text('body');
                $table->timestamps();

                $table->index(['support_ticket_id', 'created_at']);
            });
        }

        if (Schema::hasColumn('support_tickets', 'message')) {
            foreach (DB::table('support_tickets')->orderBy('id')->get() as $ticket) {
                if ($ticket->message) {
                    DB::table('support_messages')->insert([
                        'support_ticket_id' => $ticket->id,
                        'user_id' => $ticket->user_id,
                        'is_staff' => false,
                        'body' => $ticket->message,
                        'created_at' => $ticket->created_at,
                        'updated_at' => $ticket->updated_at,
                    ]);
                }
            }

            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn(['name', 'email', 'message', 'ip_address', 'read_at']);
            });
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'status')) {
                $table->string('status')->default('open')->after('download_id');
            }

            if (! Schema::hasColumn('support_tickets', 'user_last_read_at')) {
                $table->timestamp('user_last_read_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('support_tickets', 'staff_last_read_at')) {
                $table->timestamp('staff_last_read_at')->nullable()->after('user_last_read_at');
            }

            if (! Schema::hasColumn('support_tickets', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('staff_last_read_at');
            }

            if (! Schema::hasColumn('support_tickets', 'awaiting_staff')) {
                $table->boolean('awaiting_staff')->default(true)->after('last_message_at');
            }
        });

        DB::table('support_tickets')->whereNull('user_id')->delete();

        DB::table('support_tickets')->update([
            'last_message_at' => DB::raw('COALESCE(last_message_at, updated_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');

        if (! Schema::hasColumn('support_tickets', 'message')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->text('message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('read_at')->nullable();
            });
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $columns = ['status', 'user_last_read_at', 'staff_last_read_at', 'last_message_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('support_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
