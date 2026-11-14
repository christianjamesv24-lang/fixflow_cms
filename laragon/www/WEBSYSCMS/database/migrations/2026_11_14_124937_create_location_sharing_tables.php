<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_requests', 'latitude')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->decimal('latitude', 10, 7)->nullable();
            });
        }

        if (! Schema::hasColumn('service_requests', 'longitude')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->decimal('longitude', 10, 7)->nullable();
            });
        }

        if (! Schema::hasColumn('service_requests', 'location_accuracy')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->decimal('location_accuracy', 10, 2)->nullable();
            });
        }

        $usersIdIsUnsigned = $this->isUnsignedInteger('users', 'id');
        $requestsIdIsUnsigned = $this->isUnsignedInteger('service_requests', 'id');

        if (! Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) use ($usersIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'customer_id', $usersIdIsUnsigned);
                $table->string('label', 80);
                $table->string('address', 255);
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('accuracy', 10, 2)->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'id']);
            });
        }

        $this->alignIntegerColumn('customer_addresses', 'customer_id', 'users');
        $this->ensureForeignKey('customer_addresses', 'customer_id', 'users');
        $this->ensureIndex('customer_addresses', ['customer_id', 'id']);

        if (! Schema::hasTable('technician_live_locations')) {
            Schema::create('technician_live_locations', function (Blueprint $table) use ($usersIdIsUnsigned, $requestsIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'request_id', $requestsIdIsUnsigned);
                $this->addIntegerColumn($table, 'technician_id', $usersIdIsUnsigned);
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('accuracy', 10, 2)->nullable();
                $table->timestamp('updated_at')->useCurrent();

                $table->unique('request_id');
            });
        }

        $this->alignIntegerColumn('technician_live_locations', 'request_id', 'service_requests');
        $this->alignIntegerColumn('technician_live_locations', 'technician_id', 'users');
        $this->ensureForeignKey('technician_live_locations', 'request_id', 'service_requests');
        $this->ensureForeignKey('technician_live_locations', 'technician_id', 'users');
        $this->ensureUniqueIndex('technician_live_locations', 'request_id');

        if (! Schema::hasTable('request_chat_messages')) {
            Schema::create('request_chat_messages', function (Blueprint $table) use ($usersIdIsUnsigned, $requestsIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'request_id', $requestsIdIsUnsigned);
                $this->addIntegerColumn($table, 'sender_id', $usersIdIsUnsigned);
                $this->addIntegerColumn($table, 'receiver_id', $usersIdIsUnsigned);
                $table->string('message_type', 20)->default('text');
                $table->text('message');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('accuracy', 10, 2)->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['request_id', 'id']);
            });
        }

        $this->alignIntegerColumn('request_chat_messages', 'request_id', 'service_requests');
        $this->alignIntegerColumn('request_chat_messages', 'sender_id', 'users');
        $this->alignIntegerColumn('request_chat_messages', 'receiver_id', 'users');
        $this->ensureForeignKey('request_chat_messages', 'request_id', 'service_requests');
        $this->ensureForeignKey('request_chat_messages', 'sender_id', 'users');
        $this->ensureForeignKey('request_chat_messages', 'receiver_id', 'users');
        $this->ensureIndex('request_chat_messages', ['request_id', 'id']);
    }

    public function down(): void
    {
        Schema::dropIfExists('request_chat_messages');
        Schema::dropIfExists('technician_live_locations');
        Schema::dropIfExists('customer_addresses');

        foreach (['latitude', 'longitude', 'location_accuracy'] as $column) {
            if (Schema::hasColumn('service_requests', $column)) {
                Schema::table('service_requests', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function addIntegerColumn(Blueprint $table, string $column, bool $isUnsigned): void
    {
        if ($isUnsigned) {
            $table->unsignedInteger($column);

            return;
        }

        $table->integer($column);
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

    private function alignIntegerColumn(string $table, string $column, string $referencedTable): void
    {
        $columnIsUnsigned = $this->isUnsignedInteger($table, $column);
        $referencedColumnIsUnsigned = $this->isUnsignedInteger($referencedTable, 'id');

        if ($columnIsUnsigned === $referencedColumnIsUnsigned) {
            return;
        }

        $foreignKey = $this->foreignKeyForColumn($table, $column);

        if ($foreignKey !== null) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                $blueprint->dropForeign($foreignKey['name']);
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

        if ($foreignKey !== null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable): void {
            $blueprint->foreign($column)->references('id')->on($referencedTable)->cascadeOnDelete();
        });
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

    private function ensureIndex(string $table, array $columns): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === $columns) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->index($columns);
        });
    }

    private function ensureUniqueIndex(string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === [$column]) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->unique($column);
        });
    }
};
