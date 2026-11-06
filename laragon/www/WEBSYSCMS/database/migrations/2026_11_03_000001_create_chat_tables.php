<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_one');
                $table->integer('user_two');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('user_one')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('user_two')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['user_one', 'user_two']);
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('conversation_id');
                $table->integer('sender_id');
                $table->integer('receiver_id');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('conversation_id')->references('id')->on('chat_conversations')->cascadeOnDelete();
                $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
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
};
