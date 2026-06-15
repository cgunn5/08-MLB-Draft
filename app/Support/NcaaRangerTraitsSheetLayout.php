<?php

namespace App\Support;

/**
 * NCAA data library → NCAA profile: slot keys map to resolver block keys.
 *
 * @phpstan-type BlockDef array{type: 'single_year'|'multi_year'|'pitch_rows', slugs: list<string>, pitch_col?: string, pitches?: list<string>}
 */
final class NcaaRangerTraitsSheetLayout
{
    /**
     * @return array<string, BlockDef>
     */
    public static function blocks(): array
    {
        return [
            'ncaa_perf_ncaa' => [
                'type' => 'multi_year',
                'slugs' => ['pa', 'avg', 'slg', 'iso', 'ops', 'xwoba', 'woba', 'awoba'],
            ],
            'ncaa_approach_ncaa' => [
                'type' => 'multi_year',
                'slugs' => [
                    'k_pct', 'ak_pct', 'bb_pct', 'abb_pct', 'k_bb', 'ak_bb',
                    'sw_pct', 'ch_pct', 'swdec', 'swm_pct', 'iz_swm_pct',
                ],
            ],
            'ncaa_hunt' => [
                'type' => 'multi_year',
                'slugs' => ['cov_pct', 'hunt_pct', 'lt2k_hunt_pct', 'nz_xops', 'onz_xops', 'delta'],
            ],
            'ncaa_adjust_pitch' => [
                'type' => 'pitch_rows',
                'pitch_col' => 'pitch',
                'pitches' => ['FB', 'BB', 'OS'],
                'slugs' => ['p', 'bipx', 'ops', 'iso', 'ev95', 'gb_pct', 'swm', 'izswm', 'ch_pct'],
            ],
            'ncaa_platoon' => [
                'type' => 'multi_year',
                'slugs' => ['ops_vs_r', 'iso_vs_r', 'k_bb_vs_r', 'ops_vs_l', 'iso_vs_l', 'k_bb_vs_l'],
            ],
            'ncaa_engine_ncaa' => [
                'type' => 'multi_year',
                'slugs' => ['ev70', 'ev95', 'max_ev', 'bip110', 'barrel_pct', 'gb_pct', 'ld_pct'],
            ],
        ];
    }

    /**
     * @return array<string, array{section: string, table: string, blocks: list<string>}>
     */
    public static function ncaaProfileSlotDefinitions(): array
    {
        return [
            'performance_ncaa' => [
                'section' => 'Performance',
                'table' => 'NCAA',
                'blocks' => ['ncaa_perf_ncaa'],
            ],
            'approach_ncaa' => [
                'section' => 'K-Zone Control',
                'table' => 'NCAA',
                'blocks' => ['ncaa_approach_ncaa'],
            ],
            'approach_hunt' => [
                'section' => 'K-Zone Control',
                'table' => 'Hunt%',
                'blocks' => ['ncaa_hunt'],
            ],
            'adjustability_overall' => [
                'section' => 'Adjustability',
                'table' => 'Overall',
                'blocks' => ['ncaa_adjust_pitch'],
            ],
            'adjustability_pitch' => [
                'section' => 'Adjustability',
                'table' => 'Pitch Types',
                'blocks' => ['ncaa_adjust_pitch'],
            ],
            'engine_overall' => [
                'section' => 'Engine',
                'table' => 'Overall',
                'blocks' => ['ncaa_engine_ncaa'],
            ],
            'platoon_ncaa' => [
                'section' => 'Platoon',
                'table' => 'NCAA',
                'blocks' => ['ncaa_platoon'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ncaaProfileSlotKeys(): array
    {
        return array_keys(self::ncaaProfileSlotDefinitions());
    }

    /**
     * @return list<string>
     */
    public static function blockKeysForProfileSlot(string $slot): array
    {
        return self::ncaaProfileSlotDefinitions()[$slot]['blocks'] ?? [];
    }

    /**
     * @return list<array{section: string, tables: list<array{key: string, label: string}>}>
     */
    public static function ncaaProfileFeedUiGroups(): array
    {
        $defs = self::ncaaProfileSlotDefinitions();
        $order = [
            'Performance' => ['performance_ncaa'],
            'K-Zone Control' => ['approach_ncaa', 'approach_hunt'],
            'Platoon' => ['platoon_ncaa'],
            'Adjustability' => ['adjustability_overall', 'adjustability_pitch'],
            'Engine' => ['engine_overall'],
        ];
        $out = [];
        foreach ($order as $section => $keys) {
            $tables = [];
            foreach ($keys as $k) {
                $tables[] = [
                    'key' => $k,
                    'label' => $defs[$k]['table'],
                ];
            }
            $out[] = ['section' => $section, 'tables' => $tables];
        }

        return $out;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function slugAliases(): array
    {
        $base = HsRangerTraitsSheetLayout::slugAliases();

        return array_merge($base, [
            'xwoba' => ['xwoba', 'expectedwoba', 'x woba', 'xwobaa'],
            'woba' => ['woba', 'weightedonbase', 'weightedonbaseaverage', 'weightedonbaseavg'],
            'awoba' => ['awoba', 'adjustedwoba', 'adjustedwobaa', 'adjwoba', 'awobaa'],
            'ak_pct' => ['ak', 'akpct', 'akpercent', 'adjustedk', 'adjustedkpct', 'adjk', 'adj k', 'adj k%', 'ak_pct'],
            'abb_pct' => ['abb', 'abbpct', 'abbpercent', 'adjustedbb', 'adjustedbbpct', 'adjbb', 'adj bb', 'adj bb%', 'abb_pct'],
            'k_bb' => ['kbb', 'ktobb', 'ktob', 'kto bb', 'k_bb', 'k/bb', 'kbb ratio'],
            'ak_bb' => ['akbb', 'aktobb', 'ak_bb', 'ak/bb', 'adj k/bb', 'adjkbb'],
            'ops_vs_r' => array_values(array_unique(array_merge(
                $base['ops_vs_r'] ?? [],
                ['ops vs r', 'ops vs. r', 'ops_vs_r'],
            ))),
            'iso_vs_r' => ['iso vs r', 'isovsr', 'iso vs. r', 'iso_vs_r'],
            'k_bb_vs_r' => ['k/bb vs r', 'kbb vs r', 'k_bb vs r', 'kbbvsr', 'k/bb vs. r', 'k_bb_vs_r'],
            'ops_vs_l' => array_values(array_unique(array_merge(
                $base['ops_vs_l'] ?? [],
                ['ops vs l', 'ops vs. l', 'ops_vs_l'],
            ))),
            'iso_vs_l' => ['iso vs l', 'isovsl', 'iso vs. l', 'iso_vs_l'],
            'k_bb_vs_l' => ['k/bb vs l', 'kbb vs l', 'k_bb vs l', 'kbbvsl', 'k/bb vs. l', 'k_bb_vs_l'],
            'bip110' => ['bip110', 'bip110plus', '110plus', '110', 'bip 110', 'bip 110+', 'bipex110'],
            'barrel_pct' => array_values(array_unique(array_merge(
                $base['barrel_pct'] ?? [],
                ['brl', 'brlpct', 'brl pct', 'brl%']
            ))),
            'max_ev' => array_values(array_unique(array_merge(
                $base['max_ev'] ?? ['maxev'],
                ['mev', 'maxexit', 'max exit']
            ))),
            'gb_pct' => array_values(array_unique(array_merge(
                $base['gb_pct'] ?? [],
                ['gb_r', 'gb r', 'gb-r']
            ))),
            'cov_pct' => ['cov', 'cov%', 'covpct', 'covpercent', 'coverage', 'coverage%', 'cov_pct'],
            'hunt_pct' => ['hunt', 'hunt%', 'huntpct', 'huntpercent', 'hunt_pct'],
            'lt2k_hunt_pct' => [
                '<2k hunt%', '<2k hunt', 'lt2k hunt%', 'lt2k hunt', 'lt2k_hunt%',
                'under2k hunt%', 'under 2k hunt%', '2k hunt%', 'lt2khunt%',
            ],
            'nz_xops' => ['nz xops', 'nz_xops', 'nzxops', 'nz xops', 'nz x ops'],
            'onz_xops' => ['onz xops', 'onz_xops', 'onzxops', 'o nz xops', 'o nz xops', 'onz x ops'],
            'delta' => ['Δ', 'delta', 'diff', 'change', 'nz delta', 'xops delta'],
        ]);
    }
}
