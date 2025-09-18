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
        Schema::create('work_calendar_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->year('year');
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->foreignId('continuity_template_id')->nullable()->constrained('work_calendar_templates')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_calendar_templates');
    }
};
