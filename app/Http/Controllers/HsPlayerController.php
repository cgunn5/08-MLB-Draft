<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\View\View;

class HsPlayerController extends Controller
{
    public function show(Player $player): View
    {
        if ($player->player_pool !== 'hs') {
            abort(404);
        }

        $hsPlayers = Player::query()->hs()->orderedByName()->get();

        return view('hs.players.show', [
            'player' => $player,
            'hsPlayers' => $hsPlayers,
        ]);
    }
}
