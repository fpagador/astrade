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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('work_calendar_template_id')
                ->nullable()
                ->after('company_id');

            $table->foreign('work_calendar_template_id')
                ->references('id')->on('work_calendar_templates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['work_calendar_template_id']);
            $table->dropColumn('work_calendar_template_id');
        });
    }
};
