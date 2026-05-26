<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

/**
 * Imports application tables from a bundle's database.sqlite into the live connection
 * (MySQL on Laravel Cloud, etc.).
 */
final class ApplicationBundleSqliteTableCopier
{
    /**
     * Child tables first on delete; parents first on insert.
     *
     * @var list<string>
     */
    private const APPLICATION_TABLES = [
        'users',
        'players',
        'data_source_uploads',
        'working_board_entries',
    ];

    /**
     * @return array<string, int> table => row count
     */
    public function copyFromSqliteFile(string $sqlitePath): array
    {
        if (! is_file($sqlitePath)) {
            throw new RuntimeException("Bundle database file not found: {$sqlitePath}");
        }

        $sqlite = new PDO('sqlite:'.$sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $counts = [];

        DB::transaction(function () use ($sqlite, &$counts): void {
            Schema::disableForeignKeyConstraints();

            foreach (array_reverse(self::APPLICATION_TABLES) as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->delete();
            }

            foreach (self::APPLICATION_TABLES as $table) {
                if (! Schema::hasTable($table) || ! $this->sqliteTableExists($sqlite, $table)) {
                    continue;
                }

                $counts[$table] = $this->copyTable($sqlite, $table);
            }

            Schema::enableForeignKeyConstraints();
        });

        return $counts;
    }

    private function sqliteTableExists(PDO $sqlite, string $table): bool
    {
        $statement = $sqlite->prepare(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table"
        );
        $statement->execute(['table' => $table]);

        return $statement->fetchColumn() !== false;
    }

    private function copyTable(PDO $sqlite, string $table): int
    {
        $columns = $this->sharedColumns($sqlite, $table);
        if ($columns === []) {
            return 0;
        }

        $columnList = implode(', ', array_map(static fn (string $column): string => '"'.$column.'"', $columns));
        $rows = $sqlite->query("SELECT {$columnList} FROM \"{$table}\"")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return 0;
        }

        $payload = [];
        foreach ($rows as $row) {
            $record = [];
            foreach ($columns as $column) {
                $record[$column] = $row[$column] ?? null;
            }
            $payload[] = $record;
        }

        foreach (array_chunk($payload, 100) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        return count($payload);
    }

    /**
     * @return list<string>
     */
    private function sharedColumns(PDO $sqlite, string $table): array
    {
        $sqliteColumns = [];
        foreach ($sqlite->query("PRAGMA table_info(\"{$table}\")")->fetchAll(PDO::FETCH_ASSOC) as $info) {
            $sqliteColumns[] = (string) $info['name'];
        }

        $targetColumns = Schema::getColumnListing($table);

        return array_values(array_intersect($sqliteColumns, $targetColumns));
    }
}
