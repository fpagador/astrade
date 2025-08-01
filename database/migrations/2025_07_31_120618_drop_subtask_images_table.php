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
        Schema::dropIfExists('subtask_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('subtask_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('subtask_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('subtask_id')->references('id')->on('subtasks')->onDelete('cascade');
        });
    }
};
