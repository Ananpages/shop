<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
        });

        // Wishlist
        Schema::create('wishlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // Conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('buyer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('seller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->integer('buyer_unread')->default(0);
            $table->integer('seller_unread')->default(0);
            $table->timestamps();
            $table->unique(['buyer_id', 'shop_id']);
        });

        // Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('content');
            $table->enum('type', ['text', 'product', 'image'])->default('text');
            $table->uuid('product_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Notifications
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('system');
            $table->uuid('reference_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Recently Viewed
        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('reviews');
    }
};
