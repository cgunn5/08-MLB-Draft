<?php

namespace App\Support;

use App\Models\Player;

/**
 * Matches {@see resources/js/app.js} playerListTable.cellHeatStyle for rank columns (RK, MDL, etc.).
 */
final class PlayerListRankCellHeat
{
    public static function inlineStyle(?int $rank): ?string
    {
        return self::heatFromNumericRank($rank, self::aggregateRankValues());
    }

    /**
     * Heat for model draft list rank ({@see PlayerListController} "mdl" / {@see Player::modelDraftListRank}).
     */
    public static function inlineStyleForModelDraftRank(?int $rank): ?string
    {
        return self::heatFromNumericRank($rank, self::modelDraftRankValues());
    }

    /**
     * @return list<float>
     */
    private static function aggregateRankValues(): array
    {
        return Player::query()
            ->whereNotNull('aggregate_rank')
            ->pluck('aggregate_rank')
            ->map(static fn ($v) => (float) $v)
            ->values()
            ->all();
    }

    /**
     * @return list<float>
     */
    private static function modelDraftRankValues(): array
    {
        return Player::query()
            ->whereNotNull('source_ranks')
            ->pluck('source_ranks')
            ->map(static function ($json) {
                if (! is_array($json) || ! array_key_exists('model', $json) || $json['model'] === null || $json['model'] === '') {
                    return null;
                }

                return (float) $json['model'];
            })
            ->filter(static fn ($v) => $v !== null)
            ->values()
            ->all();
    }

    /**
     * @param  list<float>  $nums
     */
    private static function heatFromNumericRank(?int $rank, array $nums): ?string
    {
        if ($rank === null || $nums === []) {
            return null;
        }

        $min = min($nums);
        $max = max($nums);
        if ($min === $max) {
            return null;
        }

        $n = (float) $rank;
        $t = ($n - $min) / ($max - $min);

        $redR = 255;
        $redG = 0;
        $redB = 0;
        $blueR = 90;
        $blueG = 125;
        $blueB = 188;

        if ($t <= 0.5) {
            $linearU = $t / 0.5;
            $u = $linearU ** 1.12;
            $r = (int) round($redR + (255 - $redR) * $u);
            $g = (int) round($redG + (255 - $redG) * $u);
            $bch = (int) round($redB + (255 - $redB) * $u);
        } else {
            $linearU = ($t - 0.5) / 0.5;
            $u = 1 - (1 - $linearU) ** 2;
            $r = (int) round(255 + ($blueR - 255) * $u);
            $g = (int) round(255 + ($blueG - 255) * $u);
            $bch = (int) round(255 + ($blueB - 255) * $u);
        }

        $whiteText = $t <= 0.2 || $t >= 0.8;
        $color = $whiteText ? '#ffffff' : '#111827';

        return sprintf('background-color: rgb(%d,%d,%d); color: %s;', $r, $g, $bch, $color);
    }
}
