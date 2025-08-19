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
        Schema::create('work_calendar_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('work_calendar_templates')->onDelete('cascade');
            $table->date('date');
            $table->enum('day_type', ['holiday', 'weekend', 'workday'])->default('workday');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_calendar_days');
    }
};
