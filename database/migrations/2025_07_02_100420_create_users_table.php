<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('dni')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('username')->nullable();
            $table->string('password');
            $table->string('photo')->nullable();
            $table->string('work_schedule')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->enum('notification_type', ['none', 'visual', 'visual_audio'])->default('none');
            $table->boolean('can_receive_notifications')->default(true);
            $table->boolean('can_be_called')->default(false);
            $table->string('phone')->nullable();
            $table->rememberToken()->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
