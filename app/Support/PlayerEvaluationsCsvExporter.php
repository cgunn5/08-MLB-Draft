<?php

namespace App\Support;

use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;

final class PlayerEvaluationsCsvExporter
{
    /**
     * @return list<string>
     */
    public static function columnHeaders(): array
    {
        return [
            'Player',
            'Round',
            'Role Grade',
            'Conf',
            'Risk',
            'Perf Grade',
            'K-Zone Grade',
            'Damage Grade',
            'Adj Grade',
            'Platoon Grade',
            'Swing Grade',
            'Master Take',
            'Perf Take',
            'K-Zone Take',
            'Damage Take',
            'Adj Take',
            'Platoon Take',
            'Swing Take',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function rowsForUser(User $user): array
    {
        $entriesByBoardPlayer = $this->indexBoardEntries($user);

        return Player::query()
            ->orderedForPlayerList()
            ->get()
            ->map(fn (Player $player): array => $this->rowForPlayer($player, $entriesByBoardPlayer))
            ->all();
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
     * @return list<string>
     */
    private function rowForPlayer(Player $player, array $entriesByBoardPlayer): array
    {
        $pool = PlayerNoteFieldKeys::canonicalPoolForNotes((string) $player->player_pool);
        $isHs = $pool === 'hs';
        $boardEntry = $this->boardEntryForPlayer($player, $entriesByBoardPlayer);

        return [
            $this->playerLabel($player),
            $boardEntry !== null ? WorkingBoardEntry::roundDisplayLabel((string) $boardEntry->round_key) : '',
            $this->formatGrade($player->grade_role),
            $boardEntry?->confidence ?? '',
            $boardEntry !== null && $boardEntry->risk !== null && $boardEntry->risk !== ''
                ? WorkingBoardEntry::riskDisplayLabel((string) $boardEntry->risk)
                : '',
            $this->formatGrade($player->grade_perf),
            $this->formatGrade($player->grade_approach),
            $this->formatGrade($player->grade_damage),
            $isHs ? $this->formatGrade($player->grade_contact) : $this->formatGrade($player->grade_adj),
            $isHs ? '' : $this->formatGrade($player->grade_contact),
            $this->formatGrade($player->grade_swing),
            $this->textCell($player->master_take),
            $this->textCell($player->note_performance),
            $this->textCell($player->note_approach_miss),
            $this->textCell($player->note_engine),
            $this->textCell($player->note_pitch_coverage),
            $isHs ? '' : $this->textCell($player->note_left_right),
            $this->textCell($player->note_swing),
        ];
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

    private function playerLabel(Player $player): string
    {
        return strtoupper((string) $player->last_name).', '.strtoupper((string) $player->first_name);
    }

    private function formatGrade(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        if (! is_numeric((string) $raw)) {
            return (string) $raw;
        }

        $value = (float) $raw;

        return abs($value - round($value)) < 1e-9
            ? (string) (int) round($value)
            : number_format($value, 1, '.', '');
    }

    private function textCell(mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }

        return trim((string) $raw);
    }
}
