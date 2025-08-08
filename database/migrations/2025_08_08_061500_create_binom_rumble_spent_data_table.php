<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('binom_rumble_spent_data', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('leads');
            $table->decimal('revenue', 12, 2);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('report_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binom_rumble_spent_data');
    }
};
