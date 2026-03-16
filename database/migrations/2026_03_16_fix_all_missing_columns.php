<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Create wishlists table
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('user_id');
                $table->string('product_id');
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        // ✅ Fix messages table — add missing columns
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'type')) {
                $table->string('type')->default('text')->after('content');
            }
            if (!Schema::hasColumn('messages', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('type');
            }
            if (!Schema::hasColumn('messages', 'meta')) {
                $table->text('meta')->nullable()->after('is_read');
            }
        });

        // ✅ Fix conversations table — add missing columns
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
            if (!Schema::hasColumn('conversations', 'seller_id')) {
                $table->string('seller_id')->nullable()->after('buyer_id');
            }
        });

        // ✅ Fix shops table — add verification and custom_slug columns
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('status');
            }
            if (!Schema::hasColumn('shops', 'verification_status')) {
                $table->enum('verification_status', ['none','pending','approved','rejected'])->default('none')->after('is_verified');
            }
            if (!Schema::hasColumn('shops', 'verification_note')) {
                $table->text('verification_note')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('shops', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_note');
            }
            if (!Schema::hasColumn('shops', 'verification_expires_at')) {
                $table->timestamp('verification_expires_at')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('shops', 'custom_slug')) {
                $table->string('custom_slug')->nullable()->unique()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
