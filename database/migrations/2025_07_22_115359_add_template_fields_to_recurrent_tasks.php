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
        Schema::table('recurrent_tasks', function (Blueprint $table) {
            $table->string('template_title')->nullable();
            $table->text('template_description')->nullable();
            $table->time('template_scheduled_time')->nullable();
            $table->integer('template_estimated_duration_minutes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurrent_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'template_title',
                'template_description',
                'template_scheduled_time',
                'template_estimated_duration_minutes'
            ]);
        });
    }
};
