<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->after('id')->index();
        });

        $subtasks = DB::table('subtasks')->whereNull('external_id')->get();
        foreach ($subtasks as $subtask) {
            DB::table('subtasks')
                ->where('id', $subtask->id)
                ->update(['external_id' => Str::uuid()]);
        }

        Schema::table('subtasks', function (Blueprint $table) {
            $table->uuid('external_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
