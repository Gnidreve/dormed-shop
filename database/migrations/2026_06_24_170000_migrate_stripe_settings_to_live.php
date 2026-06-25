<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'stripe.publishable_key')->update(['key' => 'stripe.live.publishable_key']);
        DB::table('settings')->where('key', 'stripe.secret_key')->update(['key' => 'stripe.live.secret_key']);
        DB::table('settings')->where('key', 'stripe.webhook_secret')->update(['key' => 'stripe.live.webhook_secret']);
    }

    public function down(): void
    {
    }
};
