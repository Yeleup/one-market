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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('recipient_type')->default('client')->after('source');
            $table->string('recipient_first_name')->nullable()->after('created_by_user_id');
            $table->string('recipient_last_name')->nullable()->after('recipient_first_name');
            $table->string('recipient_bin')->nullable()->after('recipient_last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_type',
                'recipient_first_name',
                'recipient_last_name',
                'recipient_bin',
            ]);
        });
    }
};
