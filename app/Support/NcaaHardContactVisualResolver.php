<?php

namespace App\Support;

use App\Models\NcaaPlayerHardContactVisual;
use App\Models\Player;
use App\Models\User;

final class NcaaHardContactVisualResolver
{
    /**
     * @return array{plate_url: ?string, zone_url: ?string}|null
     */
    public static function forPlayer(Player $player, User $user): ?array
    {
        if ($player->player_pool !== 'ncaa') {
            return null;
        }

        $visual = NcaaPlayerHardContactVisual::forUserPlayer($user, $player);
        if ($visual === null) {
            return null;
        }

        return $visual->toProfilePayload();
    }
}
