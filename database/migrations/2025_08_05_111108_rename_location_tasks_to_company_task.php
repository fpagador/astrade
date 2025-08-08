<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Elimina las claves foráneas ANTES de renombrar la tabla
        Schema::table('location_tasks', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['task_id']);
        });

        // Renombrar la tabla
        Schema::rename('location_tasks', 'company_tasks');

        // Renombrar columna y recrear claves foráneas
        Schema::table('company_tasks', function (Blueprint $table) {
            $table->renameColumn('location_id', 'company_id');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_tasks', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['task_id']);
        });

        Schema::table('company_tasks', function (Blueprint $table) {
            $table->renameColumn('company_id', 'location_id');

            $table->foreign('location_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::rename('company_tasks', 'location_tasks');
    }

};
