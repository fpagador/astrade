<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subtask_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subtask_id')->constrained('subtasks')->onDelete('cascade');
    $table->string('image_path')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('subtask_images');
    }
};
