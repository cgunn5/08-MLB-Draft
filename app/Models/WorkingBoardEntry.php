<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingBoardEntry extends Model
{
    public const ENTRY_TYPE_PLAYER = 'player';

    public const ENTRY_TYPE_TIER_DIVIDER = 'tier_divider';

    public const BOARD_MASTER = 'master';

    public const BOARD_NCAA = 'ncaa';

    public const BOARD_HS = 'hs';

    /** @var list<string> */
    public const BOARD_TYPES = [
        self::BOARD_MASTER,
        self::BOARD_NCAA,
        self::BOARD_HS,
    ];

    /** @var list<string> Left-to-right order on the working board page. */
    public const BOARD_DISPLAY_ORDER = [
        self::BOARD_NCAA,
        self::BOARD_MASTER,
        self::BOARD_HS,
    ];

    public const ROUND_COFFIN = 'coffin';

    /** @var list<list<string>> Round columns grouped into rows (4 per row, left to right). */
    public const ROUND_ROW_GROUPS = [
        ['1', '2', '3', '4'],
        ['5-7', '8-10', 'post-10', self::ROUND_COFFIN],
    ];

    /** @var list<string> Left-to-right column order on the working board. */
    public const ROUND_KEYS = ['1', '2', '3', '4', '5-7', '8-10', 'post-10', self::ROUND_COFFIN];

    /** @var array<string, string> Round key → divider / picker display label */
    public const ROUND_DISPLAY_LABELS = [
        '1' => '1st',
        '2' => '2nd',
        '3' => '3rd',
        '4' => '4th',
        '5-7' => '5th-7th',
        '8-10' => '8th-10th',
        'post-10' => 'Post 10',
        self::ROUND_COFFIN => '⚰️',
    ];

    public static function normalizeRoundKey(string $roundKey): string
    {
        return match ($roundKey) {
            '4+' => '4',
            '10+' => 'post-10',
            default => $roundKey,
        };
    }

    public static function roundDisplayLabel(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);

        return self::ROUND_DISPLAY_LABELS[$normalized] ?? $roundKey;
    }

    /** Column header / board picker label (e.g. "1st Round"). */
    public static function roundColumnLabel(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);
        if ($normalized === self::ROUND_COFFIN) {
            return self::ROUND_DISPLAY_LABELS[$normalized];
        }

        $base = self::ROUND_DISPLAY_LABELS[$normalized] ?? $roundKey;

        return $base.' Round';
    }

    /** Profile header target-round chip (ordinal / post-10 labels). */
    public static function profileHeaderTargetRoundLabel(string $roundKey): string
    {
        return self::roundDisplayLabel($roundKey);
    }

    /**
     * @return array<string, string>
     */
    public static function roundDisplayLabels(): array
    {
        $labels = [];
        foreach (self::ROUND_KEYS as $key) {
            $labels[$key] = self::roundDisplayLabel($key);
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public static function roundColumnLabels(): array
    {
        $labels = [];
        foreach (self::ROUND_KEYS as $key) {
            $labels[$key] = self::roundColumnLabel($key);
        }

        return $labels;
    }

    /** @var list<string> Board confidence scale 1–5 (empty = unset). */
    public const CONFIDENCE_OPTIONS = ['', '1', '2', '3', '4', '5'];

    /** @var list<string> Board risk scale 1–5 (empty = unset). */
    public const RISK_OPTIONS = ['', '1', '2', '3', '4', '5'];

    /** @var array<string, string> Stored risk value → display label */
    public const RISK_DISPLAY_LABELS = [
        '1' => 'H',
        '2' => 'M-H',
        '3' => 'M',
        '4' => 'L-M',
        '5' => 'L',
    ];

    public static function riskDisplayLabel(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        return self::RISK_DISPLAY_LABELS[$value] ?? $value;
    }

    protected $fillable = [
        'user_id',
        'board_type',
        'entry_type',
        'player_id',
        'round_key',
        'sort_order',
        'confidence',
        'risk',
    ];

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
