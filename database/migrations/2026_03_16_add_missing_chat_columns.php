<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Add missing columns to messages
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'type')) {
                $table->string('type')->default('text')->after('content');
            }
            if (!Schema::hasColumn('messages', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('type');
            }
        });

        // ✅ Add missing columns to conversations
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'last_message')) {
                $table->text('last_message')->nullable()->after('shop_id');
            }
            if (!Schema::hasColumn('conversations', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('last_message');
            }
            if (!Schema::hasColumn('conversations', 'buyer_unread')) {
                $table->unsignedInteger('buyer_unread')->default(0)->after('last_message_at');
            }
            if (!Schema::hasColumn('conversations', 'seller_unread')) {
                $table->unsignedInteger('seller_unread')->default(0)->after('buyer_unread');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_read']);
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['last_message', 'last_message_at', 'buyer_unread', 'seller_unread']);
        });
    }
};
