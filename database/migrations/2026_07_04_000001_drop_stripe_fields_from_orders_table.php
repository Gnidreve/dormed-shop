<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe has been removed as a payment provider (PayPal + invoice only).
     * Drops the Stripe columns from orders and purges Stripe settings rows.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn(['stripe_checkout_session_id', 'stripe_payment_intent_id']);
        });

        DB::table('settings')->where('key', 'like', 'stripe.%')->delete();

        DB::table('settings')
            ->where('key', 'payment.provider')
            ->where('value', 'stripe')
            ->update(['value' => 'paypal']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('status');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
        });
    }
};
