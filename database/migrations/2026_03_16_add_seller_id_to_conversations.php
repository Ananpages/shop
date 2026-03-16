<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add seller_id if missing
        if (!Schema::hasColumn('conversations', 'seller_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('seller_id')->nullable()->after('buyer_id');
            });
        }

        // ✅ Backfill seller_id from shops table for existing conversations
        DB::statement('
            UPDATE conversations c
            JOIN shops s ON c.shop_id = s.id
            SET c.seller_id = s.user_id
            WHERE c.seller_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('seller_id');
        });
    }
};
