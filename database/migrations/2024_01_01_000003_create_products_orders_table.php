<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('seller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->references('id')->on('categories');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('original_price', 12, 2);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('district');
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_views')->default(0);
            $table->integer('total_sales')->default(0);
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            $table->timestamps();
        });

        // Cart Items
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('buyer_id')->references('id')->on('users');
            $table->foreignUuid('shop_id')->constrained();
            $table->json('items');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 10, 2)->default(3000);
            $table->decimal('total', 12, 2);
            $table->string('delivery_district');
            $table->text('delivery_address');
            $table->string('buyer_phone');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending','accepted','preparing','out_for_delivery','delivered','cancelled'])->default('pending');
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->enum('payment_method', ['cash','mobile_money','card'])->default('cash');
            $table->timestamps();
        });

        // Order Status History
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignUuid('created_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('products');
    }
};
