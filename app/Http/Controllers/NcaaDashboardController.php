<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\NcaaRangerTraitsSheetResolver;
use Illuminate\View\View;

class NcaaDashboardController extends Controller
{
    public function index(): View
    {
        $ncaaPlayers = Player::query()->ncaa()->orderedByName()->get();
        $placeholder = Player::profilePlaceholder('ncaa');
        /** @var User $user */
        $user = auth()->user();
        $rangerSheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($placeholder, $user, null);

        $ncaaCompHeatRoutePlayer = $ncaaPlayers->isNotEmpty()
            ? ($placeholder->exists ? $placeholder : $ncaaPlayers->first())
            : null;

        return view('ncaa.players.show', [
            'player' => $placeholder,
            'ncaaPlayers' => $ncaaPlayers,
            'rangerSheet' => $rangerSheet,
            'ncaaCompHeatScope' => null,
            'ncaaCompHeatRoutePlayer' => $ncaaCompHeatRoutePlayer,
            'ncaaProfileRouteQuery' => [],
        ]);
    }
}
