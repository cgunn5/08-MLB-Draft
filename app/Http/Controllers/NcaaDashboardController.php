<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\View\View;

class NcaaDashboardController extends Controller
{
    public function index(): View
    {
        $ncaaPlayers = Player::query()->ncaa()->orderedByName()->get();

        return view('ncaa.players.show', [
            'player' => Player::profilePlaceholder('ncaa'),
            'ncaaPlayers' => $ncaaPlayers,
        ]);
    }
}
