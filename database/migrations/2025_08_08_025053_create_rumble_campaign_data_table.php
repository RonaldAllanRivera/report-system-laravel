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
        if (!Schema::hasTable('rumble_campaign_data')) {
            Schema::create('rumble_campaign_data', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('cpm', 10, 2);
                $table->integer('daily_limit')->nullable();
                $table->date('date_from');
                $table->date('date_to');
                $table->string('report_type')->default('daily');
                $table->timestamps();

                // Add indexes for better query performance
                $table->index('name');
                $table->index('date_from');
                $table->index('date_to');
                $table->index('report_type');
            });
            return;
        }

        // Table already exists; add missing columns/indexes
        Schema::table('rumble_campaign_data', function (Blueprint $table) {
            if (!Schema::hasColumn('rumble_campaign_data', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('rumble_campaign_data', 'cpm')) {
                $table->decimal('cpm', 10, 2);
            }
            if (!Schema::hasColumn('rumble_campaign_data', 'daily_limit')) {
                $table->integer('daily_limit')->nullable();
            }
            if (!Schema::hasColumn('rumble_campaign_data', 'date_from')) {
                $table->date('date_from');
            }
            if (!Schema::hasColumn('rumble_campaign_data', 'date_to')) {
                $table->date('date_to');
            }
            if (!Schema::hasColumn('rumble_campaign_data', 'report_type')) {
                $table->string('report_type')->default('daily');
            }
        });

        // Add indexes if missing
        Schema::table('rumble_campaign_data', function (Blueprint $table) {
            // Some drivers don't support conditional index checks; attempt and ignore if exists
            try { $table->index('name'); } catch (\Throwable $e) {}
            try { $table->index('date_from'); } catch (\Throwable $e) {}
            try { $table->index('date_to'); } catch (\Throwable $e) {}
            try { $table->index('report_type'); } catch (\Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rumble_campaign_data')) {
            Schema::table('rumble_campaign_data', function (Blueprint $table) {
                // Drop columns if they exist
                foreach (['name', 'cpm', 'daily_limit', 'date_from', 'date_to', 'report_type'] as $column) {
                    if (Schema::hasColumn('rumble_campaign_data', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
