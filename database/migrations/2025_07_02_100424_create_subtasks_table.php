<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subtasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->text('note')->nullable();
    $table->integer('order')->nullable();
    $table->enum('status', ['pending', 'completed'])->default('pending');
    $table->string('pictogram_path')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('subtasks');
    }
};
