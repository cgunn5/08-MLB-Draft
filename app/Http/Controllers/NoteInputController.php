<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerNotesBulkUpdateRequest;
use App\Http\Requests\PlayerNoteSectionRequest;
use App\Models\Player;
use App\Support\PlayerNoteFieldKeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteInputController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $players = Player::query()->orderedByName()->get();

        $selectedPlayer = null;
        if ($request->filled('player')) {
            $selectedPlayer = Player::query()->find($request->query('player'));
            if ($selectedPlayer === null) {
                return redirect()->route('notes.index');
            }
        }

        $notesComboboxPlayers = $players
            ->map(static function (Player $p): array {
                return [
                    'id' => $p->id,
                    'label' => strtoupper($p->last_name).', '.strtoupper($p->first_name),
                    'url' => route('notes.index', ['player' => $p->id]),
                ];
            })
            ->values()
            ->all();

        $noteSections = [];
        if ($selectedPlayer !== null) {
            $noteSections = array_map(
                static fn (array $row): array => [
                    'key' => $row['key'],
                    'label' => __($row['label']),
                ],
                PlayerNoteFieldKeys::sectionsForPool($selectedPlayer->player_pool),
            );
        }

        return view('notes.index', [
            'players' => $players,
            'selectedPlayer' => $selectedPlayer,
            'notesComboboxPlayers' => $notesComboboxPlayers,
            'noteSections' => $noteSections,
        ]);
    }

    public function updateAll(PlayerNotesBulkUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $allowed = PlayerNoteFieldKeys::forPool($player->player_pool);

        $updates = [];
        foreach ($allowed as $field) {
            $raw = $data['values'][$field] ?? null;
            $updates[$field] = ($raw === null || $raw === '') ? null : $raw;

            $gradeAttr = PlayerNoteFieldKeys::gradeAttributeForNoteField($field, $player->player_pool);
            if ($gradeAttr !== null && array_key_exists('grades', $data) && array_key_exists($field, $data['grades'])) {
                $g = $data['grades'][$field];
                $updates[$gradeAttr] = $g === null ? null : (string) $g;
            }
        }

        $player->update($updates);

        return redirect()
            ->route('notes.index', ['player' => $player->id])
            ->with('status', __('Notes saved.'));
    }

    public function updateSection(PlayerNoteSectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $raw = $data['value'] ?? null;
        $value = ($raw === null || $raw === '') ? null : $raw;

        $updates = [$data['field'] => $value];
        $gradeAttr = PlayerNoteFieldKeys::gradeAttributeForNoteField($data['field'], $player->player_pool);
        if ($gradeAttr !== null && array_key_exists('grade', $data)) {
            $g = $data['grade'];
            $updates[$gradeAttr] = $g === null ? null : (string) $g;
        }

        $player->update($updates);

        return redirect()
            ->route('notes.index', ['player' => $player->id])
            ->with('status', __('Note saved.'));
    }

    public function destroySection(PlayerNoteSectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $player->update([$data['field'] => null]);

        return redirect()
            ->route('notes.index', ['player' => $player->id])
            ->with('status', __('Note removed.'));
    }
}
