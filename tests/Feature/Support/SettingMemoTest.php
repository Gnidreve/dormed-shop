<?php

namespace Tests\Feature\Support;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_get_only_queries_once_per_key(): void
    {
        Setting::set('shop.name', 'dormed 24');

        DB::enableQueryLog();
        DB::flushQueryLog();

        Setting::get('shop.name');
        Setting::get('shop.name');
        Setting::get('shop.name');

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function test_set_updates_the_memo_so_the_next_get_sees_the_new_value(): void
    {
        Setting::set('shop.name', 'first');
        $this->assertSame('first', Setting::get('shop.name'));

        Setting::set('shop.name', 'second');
        $this->assertSame('second', Setting::get('shop.name'));
    }

    public function test_memo_does_not_leak_across_requests_in_production_model(): void
    {
        Setting::get('shop.name');
        Setting::flushMemo();

        DB::enableQueryLog();
        DB::flushQueryLog();

        Setting::get('shop.name');

        $this->assertCount(1, DB::getQueryLog());

        DB::disableQueryLog();
    }
}
