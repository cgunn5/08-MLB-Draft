<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\View\View;

class NcaaPlayerController extends Controller
{
    public function show(Player $player): View
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $ncaaPlayers = Player::query()->ncaa()->orderedByName()->get();

        return view('ncaa.players.show', [
            'player' => $player,
            'ncaaPlayers' => $ncaaPlayers,
        ]);
    }
}
