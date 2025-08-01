<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_completion_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
    $table->foreignId('subtask_id')->constrained('subtasks')->onDelete('cascade');
    $table->dateTime('completed_at');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completion_logs');
    }
};
