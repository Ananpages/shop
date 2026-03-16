<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shop_reviews')) {
            Schema::create('shop_reviews', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('shop_id');
                $table->string('user_id');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->boolean('is_verified')->default(false); // bought from shop
                $table->timestamps();

                $table->unique(['shop_id', 'user_id']); // one review per user per shop
                $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Make sure shops table has rating and total_reviews columns
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'rating')) {
                $table->decimal('rating', 3, 1)->default(0)->after('status');
            }
            if (!Schema::hasColumn('shops', 'total_reviews')) {
                $table->unsignedInteger('total_reviews')->default(0)->after('rating');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_reviews');
    }
};
