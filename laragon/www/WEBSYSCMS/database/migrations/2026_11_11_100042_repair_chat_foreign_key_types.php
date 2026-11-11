<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RELATIONSHIPS = [
        ['chat_conversations', 'user_one', 'users'],
        ['chat_conversations', 'user_two', 'users'],
        ['chat_messages', 'conversation_id', 'chat_conversations'],
        ['chat_messages', 'sender_id', 'users'],
        ['chat_messages', 'receiver_id', 'users'],
        ['user_notifications', 'user_id', 'users'],
    ];

    public function up(): void
    {
        foreach (self::RELATIONSHIPS as [$table, $column, $referencedTable]) {
            if (! Schema::hasTable($table) || ! Schema::hasTable($referencedTable)) {
                throw new RuntimeException("Cannot repair {$table}.{$column}: a related table is missing.");
            }

            $this->columnDefinition($table, $column);
            $this->columnDefinition($referencedTable, 'id');
            $this->assertNoOrphanedRows($table, $column, $referencedTable);
        }

        foreach (self::RELATIONSHIPS as [$table, $column, $referencedTable]) {
            $this->alignIntegerColumn($table, $column, $referencedTable);
            $this->ensureForeignKey($table, $column, $referencedTable);
        }
    }

    public function down(): void
    {
        foreach (self::RELATIONSHIPS as [$table, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $foreignKey = $this->foreignKeyForColumn($table, $column);

            if (($foreignKey['name'] ?? null) === $this->repairForeignKeyName($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                    $blueprint->dropForeign($foreignKey['name']);
                });
            }
        }
    }

    private function alignIntegerColumn(string $table, string $column, string $referencedTable): void
    {
        $columnIsUnsigned = $this->isUnsignedInteger($table, $column);
        $referencedColumnIsUnsigned = $this->isUnsignedInteger($referencedTable, 'id');

        if ($columnIsUnsigned === $referencedColumnIsUnsigned) {
            return;
        }

        $foreignKey = $this->foreignKeyForColumn($table, $column);

        if ($foreignKey !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $foreignKey): void {
                $blueprint->dropForeign($foreignKey['name'] ?? [$column]);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedColumnIsUnsigned): void {
            if ($referencedColumnIsUnsigned) {
                $blueprint->unsignedInteger($column)->change();

                return;
            }

            $blueprint->integer($column)->change();
        });
    }

    private function ensureForeignKey(string $table, string $column, string $referencedTable): void
    {
        $foreignKey = $this->foreignKeyForColumn($table, $column);

        if (
            $foreignKey !== null
            && $foreignKey['foreign_table'] === $referencedTable
            && $foreignKey['foreign_columns'] === ['id']
            && strtolower((string) $foreignKey['on_delete']) === 'cascade'
        ) {
            return;
        }

        if ($foreignKey !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $foreignKey): void {
                $blueprint->dropForeign($foreignKey['name'] ?? [$column]);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $table): void {
            $blueprint->foreign($column, $this->repairForeignKeyName($table, $column))
                ->references('id')
                ->on($referencedTable)
                ->cascadeOnDelete();
        });
    }

    private function assertNoOrphanedRows(string $table, string $column, string $referencedTable): void
    {
        $orphanedRows = DB::table("{$table} as child")
            ->leftJoin("{$referencedTable} as parent", "child.{$column}", '=', 'parent.id')
            ->whereNotNull("child.{$column}")
            ->whereNull('parent.id')
            ->count();

        if ($orphanedRows > 0) {
            throw new RuntimeException(
                "Cannot add the {$table}.{$column} foreign key: {$orphanedRows} rows have no matching {$referencedTable}.id."
            );
        }
    }

    private function foreignKeyForColumn(string $table, string $column): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['columns'] === [$column]) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function isUnsignedInteger(string $table, string $column): bool
    {
        $type = strtolower($this->columnDefinition($table, $column)['type']);
        $baseType = preg_replace('/\s+unsigned$/', '', $type);

        if (! in_array($baseType, ['int', 'integer'], true)) {
            throw new RuntimeException("Expected {$table}.{$column} to use an integer column.");
        }

        return str_contains($type, 'unsigned');
    }

    private function columnDefinition(string $table, string $column): array
    {
        foreach (Schema::getColumns($table) as $definition) {
            if ($definition['name'] === $column) {
                return $definition;
            }
        }

        throw new RuntimeException("Could not find {$table}.{$column}.");
    }

    private function repairForeignKeyName(string $table, string $column): string
    {
        return "{$table}_{$column}_repair_foreign";
    }
};
