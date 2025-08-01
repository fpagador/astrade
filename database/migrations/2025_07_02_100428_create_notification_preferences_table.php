<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->boolean('visual_enabled')->default(true);
    $table->boolean('audio_enabled')->default(false);
    $table->boolean('push_enabled')->default(false);
    $table->time('time_window_start')->nullable();
    $table->time('time_window_end')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
