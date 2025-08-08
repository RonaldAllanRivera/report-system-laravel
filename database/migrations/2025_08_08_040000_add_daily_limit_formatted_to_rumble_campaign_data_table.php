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
        Schema::table('rumble_campaign_data', function (Blueprint $table) {
            if (!Schema::hasColumn('rumble_campaign_data', 'daily_limit_formatted')) {
                $table->string('daily_limit_formatted')->nullable()->after('daily_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rumble_campaign_data', function (Blueprint $table) {
            if (Schema::hasColumn('rumble_campaign_data', 'daily_limit_formatted')) {
                $table->dropColumn('daily_limit_formatted');
            }
        });
    }
};
