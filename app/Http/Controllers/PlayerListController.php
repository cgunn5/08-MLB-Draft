<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerListController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = Player::query()->orderedForPlayerList();

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $like = '%'.$search.'%';
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('school', 'like', $like);
            });
        }

        $players = $query->get();

        return view('players.index', [
            'players' => $players,
            'search' => $search,
        ]);
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        Player::query()->create([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'player_pool' => $request->validated('player_pool'),
            'school' => $request->validated('school'),
            'position' => $request->validated('position'),
            'aggregate_rank' => $request->validated('aggregate_rank'),
            'aggregate_score' => $request->validated('aggregate_score'),
        ]);

        return redirect()
            ->route('players.index')
            ->with('status', __('Player added.'));
    }
}
