<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerListEntryRequest;
use App\Models\Player;
use App\Support\PlayerListSourceRanksInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlayerListController extends Controller
{
    public function index(): View
    {
        $players = Player::query()->orderedForPlayerList()->get();

        $tableRows = $players
            ->map(fn (Player $player): array => $this->tableRowArray($player))
            ->values()
            ->all();

        return view('players.index', [
            'players' => $players,
            'tableRows' => $tableRows,
        ]);
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Player::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'player_pool' => $validated['player_pool'],
            'school' => $validated['school'] ?? null,
            'position' => $validated['position'] ?? null,
            'aggregate_rank' => $validated['aggregate_rank'] ?? null,
            'aggregate_score' => $validated['aggregate_score'] ?? null,
            'source_ranks' => PlayerListSourceRanksInput::mergeIntoSourceRanks(null, $validated),
        ]);

        return redirect()
            ->route('players.index')
            ->with('status', __('Player added.'));
    }

    public function update(UpdatePlayerListEntryRequest $request, Player $player): JsonResponse
    {
        $validated = $request->validated();

        $player->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'source_ranks' => PlayerListSourceRanksInput::mergeIntoSourceRanks(
                $player->source_ranks,
                $validated,
            ),
        ]);
        $player->save();

        return response()->json([
            'row' => $this->tableRowArray($player->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tableRowArray(Player $player): array
    {
        $r = $player->source_ranks ?? [];

        return [
            'id' => $player->id,
            'aggregate_rank' => $player->aggregate_rank,
            'name' => strtoupper($player->last_name).', '.strtoupper($player->first_name),
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'player_pool' => $player->player_pool,
            'school' => $player->school,
            'position' => $player->position,
            'aggregate_score' => $player->aggregate_score !== null ? (float) $player->aggregate_score : null,
            'mdl' => isset($r['model']) ? (int) $r['model'] : null,
            'mlb' => isset($r['mlb']) ? (int) $r['mlb'] : null,
            'espn' => isset($r['espn']) ? (int) $r['espn'] : null,
            'law' => isset($r['law']) ? (int) $r['law'] : null,
            'fb' => isset($r['fangraphs']) ? (int) $r['fangraphs'] : null,
            'ba' => isset($r['ba']) ? (int) $r['ba'] : null,
            'profile_url' => match ($player->player_pool) {
                'ncaa' => route('ncaa.players.show', $player),
                'hs' => route('hs.players.show', $player),
                default => null,
            },
        ];
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('status', __('Player removed.'));
    }
}
