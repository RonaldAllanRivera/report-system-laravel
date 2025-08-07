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
        Schema::table('rumble_data', function (Blueprint $table) {
            $table->enum('report_type', ['daily', 'weekly', 'monthly'])
                  ->default('daily')
                  ->after('cpm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rumble_data', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
