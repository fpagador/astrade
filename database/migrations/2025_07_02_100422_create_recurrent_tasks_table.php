<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurrent_tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->string('days_of_week');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('recurrent_tasks');
    }
};
