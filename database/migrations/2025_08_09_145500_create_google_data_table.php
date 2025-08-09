<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('google_data')) {
            Schema::create('google_data', function (Blueprint $table) {
                $table->id();
                $table->string('account_name');
                $table->string('campaign');
                $table->decimal('cost', 12, 2)->default(0);
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->string('report_type')->nullable(); // weekly | monthly
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('google_data');
    }
};
