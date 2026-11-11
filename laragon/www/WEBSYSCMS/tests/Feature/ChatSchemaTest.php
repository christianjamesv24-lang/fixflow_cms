<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_chat_tables_have_foreign_keys_to_their_parent_records(): void
    {
        $this->assertSame(['users', ['id']], $this->foreignKeyTarget('chat_conversations', 'user_one'));
        $this->assertSame(['users', ['id']], $this->foreignKeyTarget('chat_conversations', 'user_two'));
        $this->assertSame(['chat_conversations', ['id']], $this->foreignKeyTarget('chat_messages', 'conversation_id'));
        $this->assertSame(['users', ['id']], $this->foreignKeyTarget('chat_messages', 'sender_id'));
        $this->assertSame(['users', ['id']], $this->foreignKeyTarget('chat_messages', 'receiver_id'));
        $this->assertSame(['users', ['id']], $this->foreignKeyTarget('user_notifications', 'user_id'));
    }

    private function foreignKeyTarget(string $table, string $column): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['columns'] === [$column]) {
                return [$foreignKey['foreign_table'], $foreignKey['foreign_columns']];
            }
        }

        return null;
    }
}
