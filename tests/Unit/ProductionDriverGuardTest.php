<?php

namespace Tests\Unit;

use App\Support\ProductionDriverGuard;
use Tests\TestCase;

class ProductionDriverGuardTest extends TestCase
{
    public function test_session_falls_back_to_file_when_sessions_table_is_missing(): void
    {
        config(['session.driver' => 'database']);

        ProductionDriverGuard::apply();

        $this->assertSame('file', config('session.driver'));
    }

    public function test_cache_falls_back_to_file_when_cache_table_is_missing(): void
    {
        config(['cache.default' => 'database']);

        ProductionDriverGuard::apply();

        $this->assertSame('file', config('cache.default'));
    }
}
