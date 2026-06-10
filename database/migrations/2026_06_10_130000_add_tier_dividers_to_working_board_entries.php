<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('working_board_entries', 'entry_type')) {
            return;
        }

        if ($this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['player_id']);
            });
        }

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->string('entry_type', 32)->default('player')->after('board_type');
            $table->unsignedBigInteger('player_id')->nullable()->change();
        });

        if (! $this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('working_board_entries', 'entry_type')) {
            return;
        }

        DB::table('working_board_entries')->where('entry_type', 'tier_divider')->delete();

        if ($this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['player_id']);
            });
        }

        Schema::table('working_board_entries', function (Blueprint $table) {
            $table->dropColumn('entry_type');
            $table->unsignedBigInteger('player_id')->nullable(false)->change();
        });

        if (! $this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            });
        }
    }

    private function foreignKeyExists(string $column): bool
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        return DB::table('information_schema.key_column_usage')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'working_board_entries')
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }
};
