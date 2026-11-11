<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userIdIsUnsigned = $this->isUnsignedInteger('users', 'id');

        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) use ($userIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'user_one', $userIdIsUnsigned);
                $this->addIntegerColumn($table, 'user_two', $userIdIsUnsigned);
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('user_one')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('user_two')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['user_one', 'user_two']);
            });
        }

        $conversationIdIsUnsigned = $this->isUnsignedInteger('chat_conversations', 'id');

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) use ($userIdIsUnsigned, $conversationIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'conversation_id', $conversationIdIsUnsigned);
                $this->addIntegerColumn($table, 'sender_id', $userIdIsUnsigned);
                $this->addIntegerColumn($table, 'receiver_id', $userIdIsUnsigned);
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('conversation_id')->references('id')->on('chat_conversations')->cascadeOnDelete();
                $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) use ($userIdIsUnsigned) {
                $table->increments('id');
                $this->addIntegerColumn($table, 'user_id', $userIdIsUnsigned);
                $table->string('title', 120);
                $table->text('body');
                $table->string('type', 40)->default('info');
                $table->boolean('is_read')->default(false);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
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
        foreach (Schema::getColumns($table) as $definition) {
            if ($definition['name'] === $column) {
                $type = strtolower($definition['type']);

                if (! in_array(preg_replace('/\s+unsigned$/', '', $type), ['int', 'integer'], true)) {
                    throw new RuntimeException("Expected {$table}.{$column} to use an integer column.");
                }

                return str_contains($type, 'unsigned');
            }
        }

        throw new RuntimeException("Could not find {$table}.{$column}.");
    }
};
