<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** MySQL identifier limit is 64 characters. */
    private const UNIQUE_BOARD_PLAYER = 'wbe_user_board_player_unique';

    private const INDEX_BOARD_ROUND_SORT = 'wbe_user_board_round_sort_idx';

    public function up(): void
    {
        if (! Schema::hasColumn('working_board_entries', 'board_type')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->string('board_type', 16)->default('hs')->after('user_id');
            });
        }

        DB::table('working_board_entries')->update(['board_type' => 'hs']);

        if ($this->usesMysql()) {
            $this->upMysql();
        } else {
            $this->rebuildIndexes();
        }
    }

    public function down(): void
    {
        if ($this->usesMysql()) {
            $this->downMysql();
        } else {
            $this->rebuildIndexes(down: true);
        }

        if (Schema::hasColumn('working_board_entries', 'board_type')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropColumn('board_type');
            });
        }
    }

    private function upMysql(): void
    {
        if ($this->foreignKeyExists('user_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if ($this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['player_id']);
            });
        }

        $this->rebuildIndexes();

        if (! $this->foreignKeyExists('user_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! $this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            });
        }
    }

    private function downMysql(): void
    {
        if ($this->foreignKeyExists('user_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if ($this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->dropForeign(['player_id']);
            });
        }

        $this->rebuildIndexes(down: true);

        if (! $this->foreignKeyExists('user_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! $this->foreignKeyExists('player_id')) {
            Schema::table('working_board_entries', function (Blueprint $table) {
                $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            });
        }
    }

    private function rebuildIndexes(bool $down = false): void
    {
        Schema::table('working_board_entries', function (Blueprint $table) use ($down) {
            if ($down) {
                $this->dropBoardTypeIndexes($table);

                if (! $this->usesMysql() || ! $this->indexExists('working_board_entries_user_id_player_id_unique')) {
                    $table->unique(['user_id', 'player_id']);
                }

                if (! $this->usesMysql() || ! $this->indexExists('working_board_entries_user_id_round_key_sort_order_index')) {
                    $table->index(['user_id', 'round_key', 'sort_order']);
                }

                return;
            }

            if (! $this->usesMysql() || $this->indexExists('working_board_entries_user_id_player_id_unique')) {
                $table->dropUnique(['user_id', 'player_id']);
            }

            if (! $this->usesMysql() || $this->indexExists('working_board_entries_user_id_round_key_sort_order_index')) {
                $table->dropIndex(['user_id', 'round_key', 'sort_order']);
            }

            if ($this->usesMysql()) {
                $this->dropBoardTypeIndexes($table);
            }

            if (! $this->usesMysql() || ! $this->indexExists(self::UNIQUE_BOARD_PLAYER)) {
                $table->unique(['user_id', 'board_type', 'player_id'], self::UNIQUE_BOARD_PLAYER);
            }

            if (! $this->usesMysql() || ! $this->indexExists(self::INDEX_BOARD_ROUND_SORT)) {
                $table->index(
                    ['user_id', 'board_type', 'round_key', 'sort_order'],
                    self::INDEX_BOARD_ROUND_SORT
                );
            }
        });
    }

    private function dropBoardTypeIndexes(Blueprint $table): void
    {
        if (! $this->usesMysql()) {
            $table->dropIndex(self::INDEX_BOARD_ROUND_SORT);
            $table->dropUnique(self::UNIQUE_BOARD_PLAYER);

            return;
        }

        if ($this->indexExists(self::INDEX_BOARD_ROUND_SORT)) {
            $table->dropIndex(self::INDEX_BOARD_ROUND_SORT);
        }

        if ($this->indexExists('working_board_entries_user_id_board_type_round_key_sort_order_index')) {
            $table->dropIndex('working_board_entries_user_id_board_type_round_key_sort_order_index');
        }

        if ($this->indexExists(self::UNIQUE_BOARD_PLAYER)) {
            $table->dropUnique(self::UNIQUE_BOARD_PLAYER);
        }

        if ($this->indexExists('working_board_entries_user_id_board_type_player_id_unique')) {
            $table->dropUnique('working_board_entries_user_id_board_type_player_id_unique');
        }
    }

    private function usesMysql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'working_board_entries')
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $column): bool
    {
        return DB::table('information_schema.key_column_usage')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'working_board_entries')
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }
};
