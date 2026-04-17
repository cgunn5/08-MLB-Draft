<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlayerListController extends Controller
{
    public function index(): View
    {
        $players = Player::query()->orderedForPlayerList()->get();

        $tableRows = $players->map(static function (Player $player): array {
            $r = $player->source_ranks ?? [];

            return [
                'id' => $player->id,
                'aggregate_rank' => $player->aggregate_rank,
                'name' => strtoupper($player->last_name).', '.strtoupper($player->first_name),
                'player_pool' => $player->player_pool,
                'school' => $player->school,
                'position' => $player->position,
                'aggregate_score' => $player->aggregate_score !== null ? (float) $player->aggregate_score : null,
                'mdl' => isset($r['model']) ? (int) $r['model'] : null,
                'mlb' => isset($r['mlb']) ? (int) $r['mlb'] : null,
                'espn' => isset($r['espn']) ? (int) $r['espn'] : null,
                'law' => isset($r['law']) ? (int) $r['law'] : null,
                'fg' => isset($r['fangraphs']) ? (int) $r['fangraphs'] : null,
                'ba' => isset($r['ba']) ? (int) $r['ba'] : null,
                'profile_url' => match ($player->player_pool) {
                    'ncaa' => route('ncaa.players.show', $player),
                    'hs' => route('hs.players.show', $player),
                    default => null,
                },
            ];
        })->values()->all();

        return view('players.index', [
            'players' => $players,
            'tableRows' => $tableRows,
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

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('status', __('Player removed.'));
    }
}
