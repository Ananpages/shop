<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('conversations', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('buyer_id')->constrained('users')->cascadeOnDelete();
        $table->foreignUuid('shop_id')->constrained('shops')->cascadeOnDelete();
        $table->timestamp('last_message_at')->nullable();
        $table->timestamps();

        $table->unique(['buyer_id', 'shop_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
