<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class AppNavigation
{
    /**
     * @return list<array{label: string, href: string, active: bool}>
     */
    public static function items(?User $user = null, ?Request $request = null): array
    {
        $user = $user ?? auth()->user();
        $request = $request ?? request();
        $isAdmin = (bool) ($user?->is_admin ?? false);

        $defs = [
            ['label' => 'HOME', 'href' => '/dashboard', 'patterns' => ['dashboard'], 'admin' => false],
            ['label' => 'BOARD', 'href' => '/board', 'patterns' => ['board*'], 'admin' => false],
            ['label' => 'PLAYERS', 'href' => '/players', 'patterns' => ['players*'], 'admin' => true],
            ['label' => 'NCAA Profiles', 'href' => '/ncaa', 'patterns' => ['ncaa', 'ncaa/*'], 'admin' => false],
            ['label' => 'HS Profiles', 'href' => '/hs', 'patterns' => ['hs', 'hs/*'], 'admin' => false],
            ['label' => 'Notes/Grades', 'href' => '/notes', 'patterns' => ['notes*'], 'admin' => true],
            ['label' => 'HS DATA', 'href' => '/data-sources', 'patterns' => ['data-sources*'], 'admin' => true],
            ['label' => 'NCAA DATA', 'href' => '/ncaa-data-sources', 'patterns' => ['ncaa-data-sources*'], 'admin' => true],
            ['label' => 'USERS', 'href' => '/admin/users', 'patterns' => ['admin/users*'], 'admin' => true],
        ];

        $activeIndex = null;
        $visible = [];
        foreach ($defs as $def) {
            if ($def['admin'] && ! $isAdmin) {
                continue;
            }
            $isActive = $request->is(...$def['patterns']);
            $visible[] = [
                'label' => $def['label'],
                'href' => url($def['href']),
                'active' => $isActive,
            ];
            if ($isActive && $activeIndex === null) {
                $activeIndex = count($visible) - 1;
            }
        }

        if ($activeIndex !== null) {
            for ($i = 0; $i < count($visible); $i++) {
                $visible[$i]['active'] = $i === $activeIndex;
            }
        }

        return $visible;
    }

    public static function currentLabel(?User $user = null, ?Request $request = null): string
    {
        foreach (self::items($user, $request) as $item) {
            if ($item['active']) {
                return $item['label'];
            }
        }

        return 'HOME';
    }
}
