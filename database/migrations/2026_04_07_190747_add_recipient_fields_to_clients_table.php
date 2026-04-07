<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('recipient_type')->default('client')->after('password');
            $table->string('recipient_first_name')->nullable()->after('recipient_type');
            $table->string('recipient_last_name')->nullable()->after('recipient_first_name');
            $table->string('recipient_bin')->nullable()->after('recipient_last_name');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_type',
                'recipient_first_name',
                'recipient_last_name',
                'recipient_bin',
            ]);
        });
    }
};
