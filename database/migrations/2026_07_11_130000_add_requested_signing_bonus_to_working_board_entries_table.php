<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->string('requested_signing_bonus', 128)->nullable()->after('drafted_status');
        });
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropColumn('requested_signing_bonus');
        });
    }
};
