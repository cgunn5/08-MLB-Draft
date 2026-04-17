<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\HsRangerTraitsSheetResolver;
use Illuminate\View\View;

class HsPlayerController extends Controller
{
    public function show(Player $player): View
    {
        if ($player->player_pool !== 'hs') {
            abort(404);
        }

        $hsPlayers = Player::query()->hs()->orderedByName()->get();
        /** @var User $user */
        $user = auth()->user();
        $rangerSheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user);

        return view('hs.players.show', [
            'player' => $player,
            'hsPlayers' => $hsPlayers,
            'rangerSheet' => $rangerSheet,
        ]);
    }
}
