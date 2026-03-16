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
    Schema::table('conversations', function (Blueprint $table) {
        $table->unsignedInteger('buyer_unread')->default(0)->after('shop_id');
        $table->unsignedInteger('seller_unread')->default(0)->after('buyer_unread');
    });
}

public function down(): void
{
    Schema::table('conversations', function (Blueprint $table) {
        $table->dropColumn(['buyer_unread', 'seller_unread']);
    });
}

    /**
     * Reverse the migrations.
     */

};
