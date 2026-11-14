<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('payment_provider', 20)->default('manual');
            $table->string('provider_checkout_id', 100)->nullable()->unique();
            $table->string('provider_payment_id', 100)->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->dateTime('paid_at')->nullable();
        });

        Schema::create('paymongo_webhook_events', function (Blueprint $table) {
            $table->string('event_id', 100)->primary();
            $table->string('event_type', 100);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropUnique('subscription_payments_provider_checkout_id_unique');
            $table->dropUnique('subscription_payments_provider_payment_id_unique');
            $table->dropColumn([
                'payment_provider',
                'provider_checkout_id',
                'provider_payment_id',
                'checkout_url',
                'paid_at',
            ]);
        });
    }
};
