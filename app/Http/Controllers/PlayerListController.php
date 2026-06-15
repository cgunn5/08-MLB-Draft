<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerListEntryRequest;
use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use App\Support\NoteGradeInputAppearance;
use App\Support\PlayerListSourceRanksInput;
use App\Support\PlayerNoteFieldKeys;
use App\Support\PlayerProfileCompleteness;
use App\Support\PlayerSheetPlaceholder;
use App\Support\WorkingBoardCellAppearance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PlayerListController extends Controller
{
    public function index(): View
    {
        $players = Player::query()->orderedForPlayerList()->get();
        $context = $this->tableContext($players);

        $tableRows = $players
            ->map(fn (Player $player): array => $this->tableRowArray($player, $context))
            ->values()
            ->all();

        return view('players.index', [
            'players' => $players,
            'tableRows' => $tableRows,
            'gradeBounds' => PlayerNoteFieldKeys::gradeBoundsForNoteField('master_take'),
            'playersReadOnly' => ! auth()->user()->canManageApplicationData(),
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
            'player_pool' => $validated['player_pool'],
            'school' => $validated['school'] ?? null,
        ]);
        $player->save();

        $fresh = $player->fresh();
        $context = $this->tableContext(Player::query()->orderedForPlayerList()->get());

        return response()->json([
            'row' => $this->tableRowArray($fresh, $context),
        ]);
    }

    /**
     * @param  Collection<int, Player>  $players
     * @return array{
     *     entriesByBoardPlayer: array<string, array<int, WorkingBoardEntry>>,
     *     batBounds: array{min: ?float, max: ?float, median: ?float},
     *     gradeBounds: array{min: int, max: int}
     * }
     */
    private function tableContext(Collection $players): array
    {
        /** @var User $user */
        $user = auth()->user()->dataOwner();

        $batValues = [];
        foreach ($players as $player) {
            $bat = $player->batGrade();
            if ($bat !== null) {
                $batValues[] = $bat;
            }
        }

        return [
            'entriesByBoardPlayer' => $this->indexBoardEntries($user),
            'batBounds' => WorkingBoardCellAppearance::percentileBoundsFromValues($batValues),
            'gradeBounds' => PlayerNoteFieldKeys::gradeBoundsForNoteField('master_take'),
        ];
    }

    /**
     * @param  array{
     *     entriesByBoardPlayer: array<string, array<int, WorkingBoardEntry>>,
     *     batBounds: array{min: ?float, max: ?float, median: ?float},
     *     gradeBounds: array{min: int, max: int}
     * }  $context
     * @return array<string, mixed>
     */
    private function tableRowArray(Player $player, array $context): array
    {
        $pool = PlayerNoteFieldKeys::canonicalPoolForNotes((string) $player->player_pool);
        $isHs = $pool === 'hs';
        $entry = $this->boardEntryForPlayer($player, $context['entriesByBoardPlayer']);
        $gradeBounds = $context['gradeBounds'];
        $bat = $player->batGrade();

        $role = $this->gradeNumeric($player->grade_role);
        $perf = $this->gradeNumeric($player->grade_perf);
        $kZone = $this->gradeNumeric($player->grade_approach);
        $adj = $this->gradeNumeric($isHs ? $player->grade_contact : $player->grade_adj);
        $platoon = $isHs ? null : $this->gradeNumeric($player->grade_contact);
        $swing = $this->gradeNumeric($player->grade_swing);

        $confidence = $entry?->confidence;
        $risk = $entry?->risk;

        return [
            'id' => $player->id,
            'name' => strtoupper($player->last_name).', '.strtoupper($player->first_name),
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'player_pool' => strtoupper($pool),
            'player_pool_key' => $pool,
            'school' => $player->school,
            'role' => $role,
            'role_display' => $this->gradeDisplay($player->grade_role),
            'role_style' => $this->gradeStyle($role, $gradeBounds),
            'conf' => $this->boardScaleNumeric($confidence),
            'conf_display' => filled($confidence) ? (string) $confidence : PlayerSheetPlaceholder::CELL,
            'conf_style' => $this->emphasisMetricStyle(WorkingBoardCellAppearance::confidenceFillStyle($confidence)),
            'risk' => $this->boardScaleNumeric($risk),
            'risk_display' => $entry !== null && filled($risk)
                ? WorkingBoardEntry::riskDisplayLabel((string) $risk)
                : PlayerSheetPlaceholder::CELL,
            'risk_style' => $this->emphasisMetricStyle(WorkingBoardCellAppearance::riskFillStyle($risk)),
            'bat' => $bat,
            'bat_display' => $bat !== null ? $this->gradeDisplay((string) $bat) : PlayerSheetPlaceholder::CELL,
            'bat_style' => $this->emphasisMetricStyle(WorkingBoardCellAppearance::percentileCellStyle($bat, $context['batBounds'])),
            'perf' => $perf,
            'perf_display' => $this->gradeDisplay($player->grade_perf),
            'perf_style' => $this->gradeStyle($perf, $gradeBounds),
            'k_zone' => $kZone,
            'k_zone_display' => $this->gradeDisplay($player->grade_approach),
            'k_zone_style' => $this->gradeStyle($kZone, $gradeBounds),
            'adj' => $adj,
            'adj_display' => $this->gradeDisplay($isHs ? $player->grade_contact : $player->grade_adj),
            'adj_style' => $this->gradeStyle($adj, $gradeBounds),
            'platoon' => $platoon,
            'platoon_display' => $isHs
                ? PlayerSheetPlaceholder::CELL
                : $this->gradeDisplay($player->grade_contact),
            'platoon_style' => $isHs
                ? NoteGradeInputAppearance::summaryCellStyle(null, $gradeBounds['min'], $gradeBounds['max'])
                : $this->gradeStyle($platoon, $gradeBounds),
            'swing' => $swing,
            'swing_display' => $this->gradeDisplay($player->grade_swing),
            'swing_style' => $this->gradeStyle($swing, $gradeBounds),
            'profile_url' => match ($pool) {
                'ncaa' => route('ncaa.players.show', $player),
                'hs' => route('hs.players.show', $player),
                default => null,
            },
            'profile_complete' => PlayerProfileCompleteness::isComplete($player),
        ];
    }

    /**
     * @return array<string, array<int, WorkingBoardEntry>>
     */
    private function indexBoardEntries(User $user): array
    {
        /** @var array<string, array<int, WorkingBoardEntry>> $indexed */
        $indexed = [];

        foreach (WorkingBoardEntry::query()->where('user_id', $user->id)->get() as $entry) {
            $indexed[$entry->board_type][$entry->player_id] = $entry;
        }

        return $indexed;
    }

    /**
     * @param  array<string, array<int, WorkingBoardEntry>>  $entriesByBoardPlayer
     */
    private function boardEntryForPlayer(Player $player, array $entriesByBoardPlayer): ?WorkingBoardEntry
    {
        $pool = PlayerNoteFieldKeys::canonicalPoolForNotes((string) $player->player_pool);
        $preferredBoard = match ($pool) {
            'hs' => WorkingBoardEntry::BOARD_HS,
            'ncaa' => WorkingBoardEntry::BOARD_NCAA,
            default => WorkingBoardEntry::BOARD_MASTER,
        };

        foreach ([$preferredBoard, WorkingBoardEntry::BOARD_MASTER] as $boardType) {
            $entry = $entriesByBoardPlayer[$boardType][$player->id] ?? null;
            if ($entry !== null) {
                return $entry;
            }
        }

        return null;
    }

    private function gradeNumeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric((string) $raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function gradeDisplay(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return PlayerSheetPlaceholder::CELL;
        }

        if (! is_numeric((string) $raw)) {
            return (string) $raw;
        }

        $value = (float) $raw;

        return abs($value - round($value)) < 1e-9
            ? (string) (int) round($value)
            : number_format($value, 1, '.', '');
    }

    /**
     * @param  array{min: int, max: int}  $gradeBounds
     */
    private function gradeStyle(?float $value, array $gradeBounds): string
    {
        return NoteGradeInputAppearance::summaryCellStyle(
            $value,
            $gradeBounds['min'],
            $gradeBounds['max'],
        );
    }

    private function boardScaleNumeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric((string) $raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function emphasisMetricStyle(string $style): string
    {
        return rtrim($style, ';').';font-weight:700!important;';
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return redirect()
            ->route('players.index')
            ->with('status', __('Player removed.'));
    }
}
