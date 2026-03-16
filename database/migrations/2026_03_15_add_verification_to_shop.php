<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
           // $table->boolean('is_verified')->default(false)->after('status');
           // $table->enum('verification_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('is_verified');
           // $table->text('verification_note')->nullable()->after('verification_status');
           // $table->timestamp('verified_at')->nullable()->after('verification_note');
            // ✅ Monthly — track when verification expires
            $table->timestamp('verification_expires_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'is_verified', 'verification_status', 'verification_note',
                'verified_at', 'verification_expires_at',
            ]);
        });
    }
};
