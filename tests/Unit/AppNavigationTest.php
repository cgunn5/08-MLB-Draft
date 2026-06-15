<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\AppNavigation;
use Illuminate\Http\Request;
use Tests\TestCase;

class AppNavigationTest extends TestCase
{
    public function test_items_mark_ncaa_player_page_active(): void
    {
        $user = User::factory()->make(['is_admin' => false]);
        $request = Request::create('/ncaa/players/42', 'GET');

        $this->assertSame('NCAA Profiles', AppNavigation::currentLabel($user, $request));
    }

    public function test_items_hide_admin_links_for_viewer(): void
    {
        $user = User::factory()->make(['is_admin' => false]);
        $request = Request::create('/ncaa', 'GET');

        $labels = array_column(AppNavigation::items($user, $request), 'label');

        $this->assertContains('BOARD', $labels);
        $this->assertContains('NCAA Profiles', $labels);
        $this->assertContains('HS Profiles', $labels);
        $this->assertNotContains('HOME', $labels);
        $this->assertNotContains('PLAYERS', $labels);
        $this->assertNotContains('NCAA DATA', $labels);
    }

    public function test_items_mark_ncaa_data_page_active(): void
    {
        $user = User::factory()->make(['is_admin' => true]);
        $request = Request::create('/ncaa-data-sources', 'GET');

        $this->assertSame('NCAA DATA', AppNavigation::currentLabel($user, $request));
    }
}
