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
        Schema::dropIfExists('task_completion_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('task_completion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subtask_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }
};
