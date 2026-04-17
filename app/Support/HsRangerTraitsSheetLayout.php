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
                'pitches' => ['FB', 'BB', 'OS', 'CH'],
                'slugs' => ['p', 'xwobacon', 'ev95', 'swm', 'izswm', 'owdec', 'bb_pct'],
            ],
        ];
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
            'swm_pct' => ['swm', 'swmpct', 'swingmiss', 'swingmisspct'],
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
            'swm' => ['swm', 'swingmiss'],
            'izswm' => ['izswm'],
            'owdec' => ['owdec'],
            'pitch' => ['pitch', 'pitchtype', 'ptype'],
            'xwobacon' => ['xwobacon'],
        ];
    }
}
