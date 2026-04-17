<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\User;
use App\Support\HsRangerTraitsSheetResolver;
use Illuminate\View\View;

class HsDashboardController extends Controller
{
    public function index(): View
    {
        $hsPlayers = Player::query()->hs()->orderedByName()->get();
        $placeholder = Player::profilePlaceholder('hs');
        /** @var User $user */
        $user = auth()->user();
        $rangerSheet = app(HsRangerTraitsSheetResolver::class)->resolve($placeholder, $user);

        return view('hs.players.show', [
            'player' => $placeholder,
            'hsPlayers' => $hsPlayers,
            'rangerSheet' => $rangerSheet,
        ]);
    }
}
