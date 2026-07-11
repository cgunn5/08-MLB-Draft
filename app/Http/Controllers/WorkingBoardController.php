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
            'boardRoundKeys' => WorkingBoardEntry::BOARD_ROUND_KEYS,
            'boardPickerRoundKeys' => WorkingBoardEntry::BOARD_PICKER_ROUND_KEYS,
            'boardRoundLabels' => WorkingBoardEntry::roundColumnLabels(),
            'boardPickerRoundLabels' => WorkingBoardEntry::pickerRoundLabels(),
            'boardConfidenceOptions' => WorkingBoardEntry::CONFIDENCE_OPTIONS,
            'boardRiskOptions' => WorkingBoardEntry::RISK_OPTIONS,
            'boardRiskLabels' => WorkingBoardEntry::RISK_DISPLAY_LABELS,
            'boardAnnotationTypes' => WorkingBoardEntry::annotationTypes(),
            'boardPanels' => $boardPanels,
            'boardPanelOrder' => [WorkingBoardEntry::BOARD_MASTER],
            'boardTypes' => [WorkingBoardEntry::BOARD_MASTER],
            'boardVisibleTypes' => [WorkingBoardEntry::BOARD_MASTER],
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
            WorkingBoardEntry::query()
                ->where('user_id', $user->id)
                ->where('board_type', WorkingBoardEntry::BOARD_MASTER)
                ->delete();

            $boardType = WorkingBoardEntry::BOARD_MASTER;
            $roundsInput = $boardsInput[$boardType]['rounds'] ?? [];
            if (! is_array($roundsInput)) {
                return;
            }

            foreach (WorkingBoardEntry::BOARD_ROUND_KEYS as $rk) {
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
                        if (in_array($entryType, [
                            WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER,
                            WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER,
                        ], true)) {
                            WorkingBoardEntry::query()->create([
                                'user_id' => $user->id,
                                'board_type' => $boardType,
                                'entry_type' => $entryType,
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
                            'quick_take' => $this->nullableBoardString($row['quick_take'] ?? null),
                            'separators' => $this->nullableBoardString($row['separators'] ?? null),
                            'red_flags' => $this->nullableBoardString($row['red_flags'] ?? null),
                            'dev_opportunities' => $this->nullableBoardString($row['dev_opportunities'] ?? null),
                            'drafted_status' => $this->nullableBoardString($row['drafted_status'] ?? null),
                            'requested_signing_bonus' => $this->nullableBoardString($row['requested_signing_bonus'] ?? null),
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
     * @param  Collection<int, WorkingBoardEntry>  $entries
     * @return array<string, list<array<string, mixed>>>
     */
    private function roundsFromEntries(Collection $entries, string $boardType): array
    {
        $rkPos = array_flip(WorkingBoardEntry::BOARD_ROUND_KEYS);
        $rounds = [];
        foreach (WorkingBoardEntry::BOARD_ROUND_KEYS as $rk) {
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
            if (! in_array($rk, WorkingBoardEntry::BOARD_ROUND_KEYS, true)) {
                continue;
            }

            $this->appendBoardEntryToRound($rounds, $rk, $entry, $boardType);
        }

        return $this->mergeLegacyRounds($rounds, $boardEntries, $boardType);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $rounds
     */
    private function appendBoardEntryToRound(array &$rounds, string $rk, WorkingBoardEntry $entry, string $boardType): void
    {
        if ($entry->entry_type === WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER) {
            $rounds[$rk][] = [
                'entry_type' => WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER,
            ];

            return;
        }

        if ($entry->entry_type === WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER) {
            return;
        }

        $player = $entry->player;
        if ($player === null) {
            return;
        }
        if (! $this->playerAllowedOnBoard($player, $boardType)) {
            return;
        }
        $rounds[$rk][] = $this->cardPayloadFromPlayer(
            $player,
            $entry->confidence,
            $entry->risk,
            $entry,
        );
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $rounds
     * @param  Collection<int, WorkingBoardEntry>  $entries
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLegacyRounds(array $rounds, Collection $entries, string $boardType): array
    {
        $legacyOrder = ['1', '2', '3', '4', '5-7', '8-10', 'post-10', WorkingBoardEntry::ROUND_COFFIN];
        foreach ($legacyOrder as $legacyRound) {
            $legacyEntries = $entries
                ->filter(fn (WorkingBoardEntry $e): bool => $e->board_type === $boardType
                    && WorkingBoardEntry::normalizeRoundKey((string) $e->round_key) === $legacyRound)
                ->sortBy('sort_order')
                ->values();

            if ($legacyEntries->isEmpty()) {
                continue;
            }

            $bucket = WorkingBoardEntry::LEGACY_ROUND_TO_BUCKET[$legacyRound] ?? null;
            if ($bucket === null) {
                continue;
            }

            if ($legacyRound === WorkingBoardEntry::ROUND_COFFIN) {
                foreach ($legacyEntries as $entry) {
                    $this->appendBoardEntryToRound(
                        $rounds,
                        WorkingBoardEntry::passRoundKeyForBucket($bucket),
                        $entry,
                        $boardType,
                    );
                }

                continue;
            }

            $belowPass = false;
            foreach ($legacyEntries as $entry) {
                if ($entry->entry_type === WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER) {
                    $belowPass = true;

                    continue;
                }

                $targetRound = $belowPass
                    ? WorkingBoardEntry::passRoundKeyForBucket($bucket)
                    : WorkingBoardEntry::targetsRoundKeyForBucket($bucket);
                $this->appendBoardEntryToRound($rounds, $targetRound, $entry, $boardType);
            }
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
    private function cardPayloadFromPlayer(Player $player, ?string $confidence, ?string $risk, ?WorkingBoardEntry $entry = null): array
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
            'quick_take' => (string) ($entry?->quick_take ?? ''),
            'separators' => (string) ($entry?->separators ?? ''),
            'red_flags' => (string) ($entry?->red_flags ?? ''),
            'dev_opportunities' => (string) ($entry?->dev_opportunities ?? ''),
            'drafted_status' => (string) ($entry?->drafted_status ?? ''),
            'requested_signing_bonus' => (string) ($entry?->requested_signing_bonus ?? ''),
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
