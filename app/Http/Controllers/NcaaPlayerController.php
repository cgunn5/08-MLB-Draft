<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\NcaaRangerTraitsSheetResolver;
use Illuminate\View\View;

class NcaaPlayerController extends Controller
{
    public function show(Player $player): View
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $ncaaPlayers = Player::query()->ncaa()->orderedByName()->get();
        /** @var User $user */
        $user = auth()->user()->dataOwner();
        $rangerSheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);

        return view('ncaa.players.show', [
            'player' => $player,
            'ncaaPlayers' => $ncaaPlayers,
            'rangerSheet' => $rangerSheet,
        ]);
    }
}
