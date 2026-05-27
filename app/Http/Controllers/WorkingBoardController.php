<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkingBoardRequest;
use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use App\Support\PlayerProfileCompleteness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkingBoardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user()->dataOwner();

        $rkPos = array_flip(WorkingBoardEntry::ROUND_KEYS);
        $entries = WorkingBoardEntry::query()
            ->where('user_id', $user->id)
            ->with('player')
            ->get()
            ->sortBy(fn (WorkingBoardEntry $e): array => [
                $rkPos[$e->round_key] ?? 99,
                $e->sort_order,
            ])
            ->values();

        $hsPlayers = Player::query()
            ->hs()
            ->orderedByName()
            ->get()
            ->filter(fn (Player $p): bool => PlayerProfileCompleteness::isComplete($p))
            ->values();

        $rounds = [];
        foreach (WorkingBoardEntry::ROUND_KEYS as $rk) {
            $rounds[$rk] = [];
        }
        foreach ($entries as $entry) {
            $player = $entry->player;
            if ($player === null || $player->player_pool !== 'hs') {
                continue;
            }
            $rk = $entry->round_key;
            if (! in_array($rk, WorkingBoardEntry::ROUND_KEYS, true)) {
                continue;
            }
            $rounds[$rk][] = $this->cardPayloadFromPlayer(
                $player,
                $entry->confidence,
                $entry->risk,
            );
        }

        $pool = $hsPlayers->map(fn (Player $p) => $this->cardPayloadFromPlayer($p, '', ''))->values()->all();

        return view('board.index', [
            'boardRoundKeys' => WorkingBoardEntry::ROUND_KEYS,
            'boardConfidenceOptions' => WorkingBoardEntry::CONFIDENCE_OPTIONS,
            'boardRiskOptions' => WorkingBoardEntry::RISK_OPTIONS,
            'boardInitialRounds' => $rounds,
            'boardPlayerPool' => $pool,
            'boardReadOnly' => ! auth()->user()->canManageApplicationData(),
        ]);
    }

    public function update(UpdateWorkingBoardRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array<string, list<array<string, mixed>>> $roundsInput */
        $roundsInput = $request->validated('rounds');

        DB::transaction(function () use ($user, $roundsInput): void {
            WorkingBoardEntry::query()->where('user_id', $user->id)->delete();
            foreach (WorkingBoardEntry::ROUND_KEYS as $rk) {
                $list = $roundsInput[$rk] ?? [];
                if (! is_array($list)) {
                    continue;
                }
                $order = 0;
                foreach ($list as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $pid = (int) ($row['player_id'] ?? 0);
                    if ($pid <= 0) {
                        continue;
                    }
                    WorkingBoardEntry::query()->create([
                        'user_id' => $user->id,
                        'player_id' => $pid,
                        'round_key' => $rk,
                        'sort_order' => $order,
                        'confidence' => $this->nullableBoardString($row['confidence'] ?? null),
                        'risk' => $this->nullableBoardString($row['risk'] ?? null),
                    ]);
                    $order++;
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('board.index')->with('status', __('Board saved.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayloadFromPlayer(Player $player, ?string $confidence, ?string $risk): array
    {
        return [
            'player_id' => $player->id,
            'confidence' => $confidence ?? '',
            'risk' => $risk ?? '',
            'last_name' => (string) $player->last_name,
            'first_name' => (string) $player->first_name,
            'position' => $player->position !== null ? (string) $player->position : '',
            'school' => $player->school !== null ? (string) $player->school : '',
            'grade_role' => $player->grade_role,
            'grade_swing' => $player->grade_swing,
        ];
    }

    private function nullableBoardString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim((string) $v);

        return $t === '' ? null : $t;
    }
}
