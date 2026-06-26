<?php

namespace App\Support;

use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;

final class ProfileHeaderBoardSummary
{
    public function __construct(
        public readonly string $roleDisplay,
        public readonly string $batGradeDisplay,
        public readonly string $targetRoundDisplay,
        public readonly string $riskDisplay,
        public readonly string $confidenceDisplay,
        public readonly string $roleCellStyle,
        public readonly string $batGradeCellStyle,
        public readonly string $riskCellStyle,
        public readonly string $confidenceCellStyle,
    ) {}

    public static function forPlayer(Player $player, User $user): self
    {
        if (! $player->exists) {
            return self::empty();
        }

        $entriesByPlayerId = WorkingBoardEntry::masterEntriesIndexedByPlayerId($user);
        $entry = WorkingBoardEntry::masterEntryForPlayer($player, $entriesByPlayerId);

        $roleGrade = is_numeric($player->grade_role) ? (float) $player->grade_role : null;
        $batGrade = $player->batGrade();

        return new self(
            roleDisplay: self::formatGrade($roleGrade),
            batGradeDisplay: self::formatGrade($batGrade),
            targetRoundDisplay: $entry !== null
                ? WorkingBoardEntry::profileHeaderTargetRoundLabel((string) $entry->round_key)
                : PlayerSheetPlaceholder::CELL,
            riskDisplay: $entry !== null && $entry->risk !== null && $entry->risk !== ''
                ? WorkingBoardEntry::riskDisplayLabel((string) $entry->risk)
                : PlayerSheetPlaceholder::CELL,
            confidenceDisplay: $entry !== null && $entry->confidence !== null && $entry->confidence !== ''
                ? (string) $entry->confidence
                : PlayerSheetPlaceholder::CELL,
            roleCellStyle: GradeScaleAppearance::profileChipCellStyle($roleGrade),
            batGradeCellStyle: BatGradeAppearance::profileChipCellStyle($batGrade),
            riskCellStyle: WorkingBoardCellAppearance::riskFillStyle($entry?->risk),
            confidenceCellStyle: WorkingBoardCellAppearance::confidenceFillStyle($entry?->confidence),
        );
    }

    private static function empty(): self
    {
        return new self(
            roleDisplay: PlayerSheetPlaceholder::CELL,
            batGradeDisplay: PlayerSheetPlaceholder::CELL,
            targetRoundDisplay: PlayerSheetPlaceholder::CELL,
            riskDisplay: PlayerSheetPlaceholder::CELL,
            confidenceDisplay: PlayerSheetPlaceholder::CELL,
            roleCellStyle: GradeScaleAppearance::profileChipCellStyle(null),
            batGradeCellStyle: BatGradeAppearance::profileChipCellStyle(null),
            riskCellStyle: WorkingBoardCellAppearance::riskFillStyle(null),
            confidenceCellStyle: WorkingBoardCellAppearance::confidenceFillStyle(null),
        );
    }

    private static function formatGrade(?float $value): string
    {
        if ($value === null) {
            return PlayerSheetPlaceholder::CELL;
        }

        return number_format($value, 1, '.', '');
    }
}
