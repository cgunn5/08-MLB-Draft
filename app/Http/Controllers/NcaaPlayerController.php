<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\HsCompHeatScope;
use App\Support\NcaaRangerTraitsSheetResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NcaaPlayerController extends Controller
{
    public function show(Request $request, Player $player): View
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $ncaaPlayers = Player::query()->ncaa()->orderedByName()->get();
        /** @var User $user */
        $user = auth()->user()->dataOwner();
        $compHeatRaw = $request->query(HsCompHeatScope::QUERY_KEY);
        $compHeatString = is_string($compHeatRaw) ? $compHeatRaw : null;
        $ncaaCompHeatScope = HsCompHeatScope::normalize($compHeatString);
        $rangerSheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, $compHeatString);

        $ncaaCompHeatRoutePlayer = $ncaaPlayers->isNotEmpty()
            ? ($player->exists ? $player : $ncaaPlayers->first())
            : null;

        return view('ncaa.players.show', [
            'player' => $player,
            'ncaaPlayers' => $ncaaPlayers,
            'rangerSheet' => $rangerSheet,
            'ncaaCompHeatScope' => $ncaaCompHeatScope,
            'ncaaCompHeatRoutePlayer' => $ncaaCompHeatRoutePlayer,
            'ncaaProfileRouteQuery' => $ncaaCompHeatScope !== null
                ? [HsCompHeatScope::QUERY_KEY => $ncaaCompHeatScope]
                : [],
        ]);
    }
}
