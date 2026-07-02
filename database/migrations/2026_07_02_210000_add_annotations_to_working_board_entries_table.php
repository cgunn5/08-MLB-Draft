<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table): void {
            $table->text('quick_take')->nullable()->after('risk');
            $table->text('separators')->nullable()->after('quick_take');
            $table->text('red_flags')->nullable()->after('separators');
            $table->text('dev_opportunities')->nullable()->after('red_flags');
        });
    }

    public function down(): void
    {
        Schema::table('working_board_entries', function (Blueprint $table): void {
            $table->dropColumn([
                'quick_take',
                'separators',
                'red_flags',
                'dev_opportunities',
            ]);
        });
    }
};
