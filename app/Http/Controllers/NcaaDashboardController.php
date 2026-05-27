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
        $user = auth()->user()->dataOwner();
        $rangerSheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($placeholder, $user, null);

        return view('ncaa.players.show', [
            'player' => $placeholder,
            'ncaaPlayers' => $ncaaPlayers,
            'rangerSheet' => $rangerSheet,
        ]);
    }
}
