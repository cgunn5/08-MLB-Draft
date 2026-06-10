<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkingBoardRequest;
use App\Support\BatGradeAppearance;
use App\Support\PlayerProfileCompleteness;
use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkingBoardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user()->dataOwner();

        $entries = WorkingBoardEntry::query()
            ->where('user_id', $user->id)
            ->with('player')
            ->get();

        $boardPanels = [];
        foreach (WorkingBoardEntry::BOARD_TYPES as $boardType) {
            $poolPlayers = $this->orderedPlayerPoolForBoard($boardType);

            $boardPanels[$boardType] = [
                'title' => match ($boardType) {
                    WorkingBoardEntry::BOARD_MASTER => __('MASTER BOARD'),
                    WorkingBoardEntry::BOARD_NCAA => __('NCAA BOARD'),
                    default => __('HS BOARD'),
                },
                'poolHint' => match ($boardType) {
                    WorkingBoardEntry::BOARD_MASTER => __('Search all players on the Players list.'),
                    WorkingBoardEntry::BOARD_NCAA => __('Search NCAA / JUCO players on the Players list.'),
                    default => __('Search high school players on the Players list.'),
                },
                'emptyPoolHint' => match ($boardType) {
                    WorkingBoardEntry::BOARD_MASTER => __('No players on the list yet.'),
                    WorkingBoardEntry::BOARD_NCAA => __('No NCAA players on the list yet.'),
                    default => __('No HS players on the list yet.'),
                },
                'initialRounds' => $this->roundsFromEntries($entries, $boardType),
                'playerPool' => $poolPlayers
                    ->map(fn (Player $p) => $this->cardPayloadFromPlayer($p, '', ''))
                    ->values()
                    ->all(),
            ];
        }

        $boardAlpineBoards = [];
        foreach ($boardPanels as $boardType => $panel) {
            $boardAlpineBoards[$boardType] = [
                'initialRounds' => $panel['initialRounds'],
                'playerPool' => $panel['playerPool'],
            ];
        }

        return view('board.index', [
            'boardRoundKeys' => WorkingBoardEntry::ROUND_KEYS,
            'boardRoundLabels' => WorkingBoardEntry::roundColumnLabels(),
            'boardConfidenceOptions' => WorkingBoardEntry::CONFIDENCE_OPTIONS,
            'boardRiskOptions' => WorkingBoardEntry::RISK_OPTIONS,
            'boardRiskLabels' => WorkingBoardEntry::RISK_DISPLAY_LABELS,
            'boardPanels' => $boardPanels,
            'boardPanelOrder' => WorkingBoardEntry::BOARD_DISPLAY_ORDER,
            'boardToggleOrder' => [
                WorkingBoardEntry::BOARD_MASTER,
                WorkingBoardEntry::BOARD_HS,
                WorkingBoardEntry::BOARD_NCAA,
            ],
            'boardToggleLabels' => [
                WorkingBoardEntry::BOARD_MASTER => __('Master'),
                WorkingBoardEntry::BOARD_HS => __('HS'),
                WorkingBoardEntry::BOARD_NCAA => __('NCAA'),
            ],
            'boardAlpineBoards' => $boardAlpineBoards,
            'boardBatGradeBounds' => BatGradeAppearance::appWideBounds(),
            'boardReadOnly' => ! auth()->user()->canManageApplicationData(),
        ]);
    }

    public function update(UpdateWorkingBoardRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array<string, array{rounds: array<string, list<array<string, mixed>>>}> $boardsInput */
        $boardsInput = $request->validated('boards');

        DB::transaction(function () use ($user, $boardsInput): void {
            WorkingBoardEntry::query()->where('user_id', $user->id)->delete();

            foreach (WorkingBoardEntry::BOARD_TYPES as $boardType) {
                $roundsInput = $boardsInput[$boardType]['rounds'] ?? [];
                if (! is_array($roundsInput)) {
                    continue;
                }

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
                        $entryType = (string) ($row['entry_type'] ?? WorkingBoardEntry::ENTRY_TYPE_PLAYER);
                        if ($entryType === WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER) {
                            WorkingBoardEntry::query()->create([
                                'user_id' => $user->id,
                                'board_type' => $boardType,
                                'entry_type' => WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER,
                                'player_id' => null,
                                'round_key' => $rk,
                                'sort_order' => $order,
                                'confidence' => null,
                                'risk' => null,
                            ]);
                            $order++;

                            continue;
                        }

                        $pid = (int) ($row['player_id'] ?? 0);
                        if ($pid <= 0) {
                            continue;
                        }
                        WorkingBoardEntry::query()->create([
                            'user_id' => $user->id,
                            'board_type' => $boardType,
                            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER,
                            'player_id' => $pid,
                            'round_key' => $rk,
                            'sort_order' => $order,
                            'confidence' => $this->nullableBoardString($row['confidence'] ?? null),
                            'risk' => $this->nullableBoardString($row['risk'] ?? null),
                        ]);
                        $order++;
                    }
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('board.index')->with('status', __('Board saved.'));
    }

    /**
     * @param  Collection<int, WorkingBoardEntry>  $entries
     * @return array<string, list<array<string, mixed>>>
     */
    private function roundsFromEntries(Collection $entries, string $boardType): array
    {
        $rkPos = array_flip(WorkingBoardEntry::ROUND_KEYS);
        $rounds = [];
        foreach (WorkingBoardEntry::ROUND_KEYS as $rk) {
            $rounds[$rk] = [];
        }

        $boardEntries = $entries
            ->filter(fn (WorkingBoardEntry $e): bool => $e->board_type === $boardType)
            ->sortBy(fn (WorkingBoardEntry $e): array => [
                $rkPos[$e->round_key] ?? 99,
                $e->sort_order,
            ])
            ->values();

        foreach ($boardEntries as $entry) {
            $rk = WorkingBoardEntry::normalizeRoundKey((string) $entry->round_key);
            if (! in_array($rk, WorkingBoardEntry::ROUND_KEYS, true)) {
                continue;
            }

            if ($entry->entry_type === WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER) {
                $rounds[$rk][] = [
                    'entry_type' => WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER,
                ];

                continue;
            }

            $player = $entry->player;
            if ($player === null) {
                continue;
            }
            if (! $this->playerAllowedOnBoard($player, $boardType)) {
                continue;
            }
            $rounds[$rk][] = $this->cardPayloadFromPlayer(
                $player,
                $entry->confidence,
                $entry->risk,
            );
        }

        return $rounds;
    }

    private function playerAllowedOnBoard(Player $player, string $boardType): bool
    {
        $pool = $player->player_pool;

        return match ($boardType) {
            WorkingBoardEntry::BOARD_HS => $pool === 'hs',
            WorkingBoardEntry::BOARD_NCAA => $pool === 'ncaa',
            WorkingBoardEntry::BOARD_MASTER => in_array($pool, ['hs', 'ncaa'], true),
            default => false,
        };
    }

    /**
     * All players from the Players list eligible for this board pane.
     *
     * @return Collection<int, Player>
     */
    private function allPlayersForBoard(string $boardType): Collection
    {
        $query = Player::query()->orderedForPlayerList();

        return match ($boardType) {
            WorkingBoardEntry::BOARD_HS => $query->hs()->get(),
            WorkingBoardEntry::BOARD_NCAA => $query->ncaa()->get(),
            default => $query->whereIn('player_pool', ['hs', 'ncaa'])->get(),
        };
    }

    /**
     * Complete profiles first, then the rest (each group keeps list order).
     *
     * @return Collection<int, Player>
     */
    private function orderedPlayerPoolForBoard(string $boardType): Collection
    {
        [$complete, $incomplete] = $this->allPlayersForBoard($boardType)
            ->partition(fn (Player $player): bool => PlayerProfileCompleteness::isComplete($player));

        return $complete->values()->concat($incomplete->values());
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayloadFromPlayer(Player $player, ?string $confidence, ?string $risk): array
    {
        $last = (string) $player->last_name;
        $first = (string) $player->first_name;
        $position = $player->position !== null ? (string) $player->position : '';
        $school = $player->school !== null ? (string) $player->school : '';
        $label = strtoupper($last).', '.strtoupper($first);

        return [
            'player_id' => $player->id,
            'player_pool' => (string) $player->player_pool,
            'confidence' => $confidence ?? '',
            'risk' => $risk ?? '',
            'last_name' => $last,
            'first_name' => $first,
            'position' => $position,
            'school' => $school,
            'label' => $label,
            'search_blob' => mb_strtolower(implode(' ', array_filter([
                $last,
                $first,
                $label,
                $position,
                $school,
            ]))),
            'grade_role' => $player->grade_role,
            'grade_perf' => $player->grade_perf,
            'grade_approach' => $player->grade_approach,
            'grade_contact' => $player->grade_contact,
            'grade_damage' => $player->grade_damage,
            'grade_adj' => $player->grade_adj,
            'grade_swing' => $player->grade_swing,
            'bat_grade' => $player->batGrade(),
            'profile_complete' => PlayerProfileCompleteness::isComplete($player),
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
