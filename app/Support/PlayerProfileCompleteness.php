<?php

namespace App\Support;

use App\Models\Player;

final class PlayerProfileCompleteness
{
    public static function isComplete(Player $player): bool
    {
        $pool = $player->player_pool;
        if ($pool === null || trim((string) $pool) === '') {
            return false;
        }

        foreach (PlayerNoteFieldKeys::forPool((string) $pool) as $field) {
            if (! self::noteFieldFilled($player, $field)) {
                return false;
            }

            $gradeAttr = PlayerNoteFieldKeys::gradeAttributeForNoteField($field, (string) $pool);
            if ($gradeAttr === null || ! self::gradeFieldFilled($player, $gradeAttr)) {
                return false;
            }
        }

        return true;
    }

    private static function noteFieldFilled(Player $player, string $field): bool
    {
        $value = $player->{$field};
        if (! filled($value)) {
            return false;
        }

        return ! PlayerSheetPlaceholder::isEmptyDisplay((string) $value);
    }

    private static function gradeFieldFilled(Player $player, string $attribute): bool
    {
        $value = $player->{$attribute};

        return filled($value) && is_numeric((string) $value);
    }
}
