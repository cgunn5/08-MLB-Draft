<?php

namespace App\Http\Controllers;

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

        if ($selectedPlayer !== null && $request->filled('edit')) {
            $allowed = PlayerNoteFieldKeys::forPool($selectedPlayer->player_pool);
            if (! in_array($request->query('edit'), $allowed, true)) {
                return redirect()->route('notes.index', ['player' => $selectedPlayer->id]);
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

    public function updateSection(PlayerNoteSectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $player = Player::query()->findOrFail($data['player_id']);
        $raw = $data['value'] ?? null;
        $value = ($raw === null || $raw === '') ? null : $raw;
        $player->update([$data['field'] => $value]);

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
