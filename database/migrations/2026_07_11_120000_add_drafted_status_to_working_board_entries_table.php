<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->string('drafted_status', 16)->nullable()->after('dev_opportunities');
        });
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropColumn('drafted_status');
        });
    }
};
