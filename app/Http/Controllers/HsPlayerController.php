<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\HsCompHeatScope;
use App\Support\HsRangerTraitsSheetResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HsPlayerController extends Controller
{
    public function show(Request $request, Player $player): View
    {
        if ($player->player_pool !== 'hs') {
            abort(404);
        }

        $hsPlayers = Player::query()->hs()->orderedByName()->get();
        /** @var User $user */
        $user = auth()->user();
        $compHeatRaw = $request->query(HsCompHeatScope::QUERY_KEY);
        $compHeatString = is_string($compHeatRaw) ? $compHeatRaw : null;
        $hsCompHeatScope = HsCompHeatScope::normalize($compHeatString);
        $rangerSheet = app(HsRangerTraitsSheetResolver::class)->resolve($player, $user, $compHeatString);

        return view('hs.players.show', [
            'player' => $player,
            'hsPlayers' => $hsPlayers,
            'rangerSheet' => $rangerSheet,
            'hsCompHeatScope' => $hsCompHeatScope,
            'hsProfileRouteQuery' => $hsCompHeatScope !== null
                ? [HsCompHeatScope::QUERY_KEY => $hsCompHeatScope]
                : [],
        ]);
    }
}
