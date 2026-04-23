<?php

namespace App\Support;

/**
 * HS Ranger Traits dashboard blocks: each slug maps to CSV columns via {@see HsRangerTraitsSheetResolver}.
 *
 * @phpstan-type BlockDef array{type: 'single_year'|'multi_year'|'pitch_rows', slugs: list<string>, pitch_col?: string, pitches?: list<string>}
 */
final class HsRangerTraitsSheetLayout
{
    /**
     * Circuit TUSA and PG are separate uploads later; only blocks listed here use the primary HS Ranger CSV.
     *
     * @return array<string, BlockDef>
     */
    public static function blocks(): array
    {
        return [
            'circuit_lonestar' => [
                'type' => 'single_year',
                'slugs' => ['g', 'pa', 'avg', 'obp', 'slg', 'ops'],
            ],
            'circuit_tusa' => [
                'type' => 'single_year',
                'slugs' => ['pa', 'avg', 'obp', 'slg', 'ops'],
            ],
            'circuit_pg' => [
                'type' => 'multi_year',
                'slugs' => ['pa', 'ops', 'avg', 'obp', 'slg', 'iso', 'bb_pct', 'k_pct'],
            ],
            'approach_lonestar' => [
                'type' => 'single_year',
                'slugs' => ['bb_pct', 'k_pct', 'sw_pct', 'swdec', 'ch_pct', 'ppa', 'swm_pct', 'iz_swm_pct'],
            ],
            'impact' => [
                'type' => 'single_year',
                'slugs' => ['iso', 'ev70', 'ev95', 'max_ev', 'bip100', 'bip105', 'barrel_pct', 'tx_barrel_pct'],
            ],
            'impact_batted_ball' => [
                'type' => 'single_year',
                'slugs' => ['gb_pct', 'fb_pct', 'ld_pct'],
            ],
            'adjust_ops_split' => [
                'type' => 'single_year',
                'slugs' => ['pa_vs_r', 'ops_vs_r', 'pa_vs_l', 'ops_vs_l'],
            ],
            'adjust_pitch' => [
                'type' => 'pitch_rows',
                'pitch_col' => 'pitch',
                'pitches' => ['FB', 'BB', 'OS'],
                'slugs' => ['p', 'bipx', 'ops', 'iso', 'ev95', 'gb_pct', 'swm', 'izswm', 'ch_pct'],
            ],
        ];
    }

    /**
     * HS Ranger profile: which dashboard tables (slots) a dataset may feed.
     * Each slot maps to one or more resolver block keys; each slot may be assigned to at most one upload per user.
     *
     * @return array<string, array{section: string, table: string, blocks: list<string>}>
     */
    public static function hsProfileSlotDefinitions(): array
    {
        return [
            'performance_overall' => [
                'section' => 'Performance',
                'table' => 'Overall',
                'blocks' => ['circuit_lonestar'],
            ],
            'performance_tusa' => [
                'section' => 'Performance',
                'table' => 'TUSA',
                'blocks' => ['circuit_tusa'],
            ],
            'performance_pg' => [
                'section' => 'Performance',
                'table' => 'Perfect Game',
                'blocks' => ['circuit_pg'],
            ],
            'approach_overall' => [
                'section' => 'Approach / Miss',
                'table' => 'Overall',
                'blocks' => ['approach_lonestar'],
            ],
            'impact_overall' => [
                'section' => 'Engine/Impact',
                'table' => 'Overall',
                'blocks' => ['impact', 'impact_batted_ball'],
            ],
            'adjustability_pitch' => [
                'section' => 'Adjustability',
                'table' => 'Pitch types',
                'blocks' => ['adjust_pitch'],
            ],
            'adjustability_lr' => [
                'section' => 'Adjustability',
                'table' => 'L/R',
                'blocks' => ['adjust_ops_split'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function hsProfileSlotKeys(): array
    {
        return array_keys(self::hsProfileSlotDefinitions());
    }

    /**
     * @return list<string>
     */
    public static function blockKeysForProfileSlot(string $slot): array
    {
        return self::hsProfileSlotDefinitions()[$slot]['blocks'] ?? [];
    }

    /**
     * UI: section headings and table labels for the data library checkboxes.
     *
     * @return list<array{section: string, tables: list<array{key: string, label: string}>}>
     */
    public static function hsProfileFeedUiGroups(): array
    {
        $defs = self::hsProfileSlotDefinitions();
        $order = [
            'Performance' => ['performance_overall', 'performance_tusa', 'performance_pg'],
            'Approach / Miss' => ['approach_overall'],
            'Engine/Impact' => ['impact_overall'],
            'Adjustability' => ['adjustability_pitch', 'adjustability_lr'],
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
        return [
            'g' => ['g', 'games'],
            'pa' => ['pa', 'plateappearances'],
            'avg' => ['avg', 'avgg', 'battingaverage'],
            'obp' => ['obp'],
            'slg' => ['slg'],
            'ops' => ['ops'],
            'bb_pct' => ['bb', 'bbpct', 'bbpercent', 'walkrate'],
            'k_pct' => ['k', 'kpct', 'kpercent', 'strikeoutrate', 'krate'],
            'sw_pct' => ['sw', 'swpct', 'swpercent', 'swingrate'],
            'swdec' => ['swdec', 'swingdec', 'swingdecisions'],
            'ch_pct' => ['ch', 'chpct', 'chpercent', 'chase'],
            'ppa' => ['ppa', 'pitchesperpa', 'pitchesperplateappearance', 'pitperpa'],
            'swm_pct' => ['swm', 'swmpct', 'swingmiss', 'swingmisspct', 'swmpercent'],
            'iz_swm_pct' => ['izswm', 'izswmpct', 'izswingmiss'],
            'iso' => ['iso', 'isolatedpower', 'isop'],
            'ev70' => ['ev70', 'ev75'],
            'ev95' => ['ev95'],
            'max_ev' => ['maxev', 'maxexitvelo', 'maxexitvelocity', 'max_ev', 'maxexitvelocitymph'],
            'bip100' => ['bip100', 'bip100plus'],
            'bip105' => ['bip105', 'bip105plus'],
            'barrel_pct' => ['barrel', 'barrelpct', 'barrelpercent', 'nitro', 'nitropct', 'nitropercent'],
            'tx_barrel_pct' => [
                'txbarrelpct',
                'txbarrelpercent',
                'txbarrel',
                'txbarrels',
                'txbrlpct',
                'txbrl',
                'tx_barrel_pct',
                'tx_barrel',
            ],
            'gb_pct' => ['gb', 'gbpct', 'gbpercent', 'groundball', 'groundballpct', 'groundballpercent'],
            'fb_pct' => ['fb', 'fbpct', 'fbpercent', 'flyball', 'flyballpct', 'flyballpercent'],
            'ld_pct' => ['ld', 'ldpct', 'ldpercent', 'linedrive', 'linedrivepct', 'linepct'],
            'pa_vs_r' => ['pavsr', 'pavrs', 'patersusright', 'pavrhp'],
            'ops_vs_r' => ['opsvsr', 'opsrvl', 'opsright', 'opsvr', 'opsr'],
            'pa_vs_l' => ['pavsl', 'pavl', 'patersusleft', 'pavlhp'],
            'ops_vs_l' => ['opsvsl', 'opslvl', 'opsleft', 'opsvl', 'opsl'],
            'p' => ['p', 'pitches', 'np', 'pitchcount'],
            'swm' => ['swm', 'swmpct', 'swingmiss', 'swingmisspct', 'swmpercent'],
            'izswm' => ['izswm', 'izswmpct', 'izswingmiss'],
            'owdec' => ['owdec'],
            'pitch' => ['pitch', 'pitchtype', 'ptype', 'type'],
            'xwobacon' => ['xwobacon'],
            'bipx' => ['bipx', 'bipxplus', 'bipex', 'bipexpected', 'xbip'],
            'bats' => ['bats', 'bathand', 'hitside', 'hit'],
            'throws' => ['throws', 'throw', 'throwhand', 'arm', 'thr'],
            'demo_age' => ['age', 'playerage', 'player age', 'seasonage', 'draftage'],
        ];
    }
}
