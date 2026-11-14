<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $usersId = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $usersIdUnsigned = str_contains($usersId['type'], 'unsigned');

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) use ($usersIdUnsigned) {
                $table->increments('id');
                $table->integer('user_id')->unsigned($usersIdUnsigned);
                $table->string('plan', 20);
                $table->decimal('amount', 10, 2);
                $table->string('status', 20)->default('pending')->index();
                $table->dateTime('current_period_start')->nullable();
                $table->dateTime('current_period_end')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status', 'current_period_end'], 'subscriptions_access_lookup');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } else {
            $indexes = Schema::getIndexes('subscriptions');
            $foreignKeys = Schema::getForeignKeys('subscriptions');
            $userId = collect(Schema::getColumns('subscriptions'))->firstWhere('name', 'user_id');
            $requiresUserIdTypeChange = $userId
                && str_contains($userId['type'], 'unsigned') !== $usersIdUnsigned;

            Schema::table('subscriptions', function (Blueprint $table) use ($indexes, $foreignKeys, $requiresUserIdTypeChange, $usersIdUnsigned) {
                if ($requiresUserIdTypeChange) {
                    $table->integer('user_id')->unsigned($usersIdUnsigned)->change();
                }

                if (! collect($indexes)->contains(fn (array $index) => $index['name'] === 'subscriptions_access_lookup')) {
                    $table->index(['user_id', 'status', 'current_period_end'], 'subscriptions_access_lookup');
                }

                if (! collect($foreignKeys)->contains(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['user_id']
                        && $foreignKey['foreign_table'] === 'users'
                        && $foreignKey['foreign_columns'] === ['id']
                )) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }
            });
        }

        if (! Schema::hasTable('subscription_payments')) {
            $subscriptionsId = collect(Schema::getColumns('subscriptions'))->firstWhere('name', 'id');
            $subscriptionsIdUnsigned = str_contains($subscriptionsId['type'], 'unsigned');

            Schema::create('subscription_payments', function (Blueprint $table) use ($usersIdUnsigned, $subscriptionsIdUnsigned) {
                $table->increments('id');
                $table->integer('subscription_id')->unsigned($subscriptionsIdUnsigned);
                $table->integer('user_id')->unsigned($usersIdUnsigned);
                $table->string('gcash_reference', 40)->unique();
                $table->string('proof_path', 255);
                $table->string('status', 20)->default('pending')->index();
                $table->integer('reviewed_by')->unsigned($usersIdUnsigned)->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->string('review_note', 255)->nullable();
                $table->timestamps();

                $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            $indexes = Schema::getIndexes('subscription_payments');
            $foreignKeys = Schema::getForeignKeys('subscription_payments');
            $columns = collect(Schema::getColumns('subscription_payments'))->keyBy('name');
            $subscriptionsId = collect(Schema::getColumns('subscriptions'))->firstWhere('name', 'id');
            $subscriptionsIdUnsigned = str_contains($subscriptionsId['type'], 'unsigned');

            Schema::table('subscription_payments', function (Blueprint $table) use ($indexes, $foreignKeys, $columns, $usersIdUnsigned, $subscriptionsIdUnsigned) {
                foreach ([
                    'user_id' => $usersIdUnsigned,
                    'reviewed_by' => $usersIdUnsigned,
                    'subscription_id' => $subscriptionsIdUnsigned,
                ] as $column => $unsigned) {
                    if (isset($columns[$column]) && str_contains($columns[$column]['type'], 'unsigned') !== $unsigned) {
                        $definition = $table->integer($column)->unsigned($unsigned);

                        if ($column === 'reviewed_by') {
                            $definition->nullable();
                        }

                        $definition->change();
                    }
                }

                if (! collect($indexes)->contains(fn (array $index) => $index['columns'] === ['gcash_reference'] && $index['unique'])) {
                    $table->unique('gcash_reference');
                }

                if (! collect($indexes)->contains(fn (array $index) => $index['columns'] === ['status'])) {
                    $table->index('status');
                }

                if (! collect($foreignKeys)->contains(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['subscription_id']
                        && $foreignKey['foreign_table'] === 'subscriptions'
                        && $foreignKey['foreign_columns'] === ['id']
                )) {
                    $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
                }

                if (! collect($foreignKeys)->contains(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['user_id']
                        && $foreignKey['foreign_table'] === 'users'
                        && $foreignKey['foreign_columns'] === ['id']
                )) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }

                if (! collect($foreignKeys)->contains(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['reviewed_by']
                        && $foreignKey['foreign_table'] === 'users'
                        && $foreignKey['foreign_columns'] === ['id']
                )) {
                    $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
    }
};
