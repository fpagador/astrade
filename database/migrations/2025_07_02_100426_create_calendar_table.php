<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->date('date');
    $table->enum('day_type', ['holiday', 'vacation', 'weekend', 'leave', 'workday']);
    $table->string('reason')->nullable();
    $table->enum('type', ['holiday', 'vacation', 'sick_leave', 'weekend']);
    $table->string('description')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar');
    }
};
