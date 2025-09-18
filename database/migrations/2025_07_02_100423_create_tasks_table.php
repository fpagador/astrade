<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->string('pictogram_path')->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->integer('reminder_minutes')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->foreignId('recurrent_task_id')->nullable()->constrained('recurrent_tasks')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
