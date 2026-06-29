<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingBoardEntry extends Model
{
    public const ENTRY_TYPE_PLAYER = 'player';

    public const ENTRY_TYPE_TIER_DIVIDER = 'tier_divider';

    public const ENTRY_TYPE_NON_TARGET_DIVIDER = 'non_target_divider';

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

    public const SECTION_TARGETS = 'targets';

    public const SECTION_PASS = 'pass';

    /** @var list<string> Round buckets (left to right on each board row). */
    public const BOARD_BUCKET_KEYS = ['1', 'tweeners-3', '4-5', '6-plus'];

    /** @var list<string> Target columns used by the add-player picker. */
    public const BOARD_PICKER_ROUND_KEYS = [
        '1-targets',
        'tweeners-3-targets',
        '4-5-targets',
        '6-plus-targets',
    ];

    /** @var list<string> Left-to-right column order on the working board. */
    public const BOARD_ROUND_KEYS = [
        '1-targets',
        'tweeners-3-targets',
        '4-5-targets',
        '6-plus-targets',
        '1-pass',
        'tweeners-3-pass',
        '4-5-pass',
        '6-plus-pass',
    ];

    /** @var list<list<string>> Board columns grouped into rows (4 per row, left to right). */
    public const ROUND_ROW_GROUPS = [
        ['1-targets', 'tweeners-3-targets', '4-5-targets', '6-plus-targets'],
        ['1-pass', 'tweeners-3-pass', '4-5-pass', '6-plus-pass'],
    ];

    /** @var list<string> Legacy per-round keys kept for migration / export fallbacks. */
    public const LEGACY_ROUND_KEYS = ['1', '2', '3', '4', '5-7', '8-10', 'post-10', self::ROUND_COFFIN, '4+', '10+'];

    /** @var list<string> */
    public const ROUND_KEYS = [...self::BOARD_ROUND_KEYS, ...self::LEGACY_ROUND_KEYS];

    /** @var array<string, string> Legacy round key → short display label */
    public const LEGACY_ROUND_DISPLAY_LABELS = [
        '1' => '1st',
        '2' => '2nd',
        '3' => '3rd',
        '4' => '4th',
        '5-7' => '5th-7th',
        '8-10' => '8th-10th',
        'post-10' => 'Post 10',
        self::ROUND_COFFIN => '⚰️',
    ];

    /** @var array<string, string> Board column key → header label */
    public const ROUND_COLUMN_LABELS = [
        '1-targets' => '1ST ROUND / TARGETS',
        'tweeners-3-targets' => '1ST TWEENERS - 3RD ROUND / TARGETS',
        '4-5-targets' => '4TH-5TH ROUND / TARGETS',
        '6-plus-targets' => '6TH ROUND + / TARGETS',
        '1-pass' => '1ST ROUND / PASS',
        'tweeners-3-pass' => '1ST TWEENERS - 3RD ROUND / PASS',
        '4-5-pass' => '4TH-5TH ROUND / PASS',
        '6-plus-pass' => '6TH ROUND + / PASS',
    ];

    /** @var array<string, string> Picker label for target columns */
    public const PICKER_ROUND_LABELS = [
        '1-targets' => '1st Round',
        'tweeners-3-targets' => '1st Tweeners - 3rd Round',
        '4-5-targets' => '4th-5th Round',
        '6-plus-targets' => '6th Round +',
    ];

    /** @var array<string, string> Profile / export bucket label */
    public const BUCKET_DISPLAY_LABELS = [
        '1' => '1st',
        'tweeners-3' => '2nd-3rd',
        '4-5' => '4th-5th',
        '6-plus' => '6th+',
    ];

    /** @var array<string, string> Legacy round key → new bucket key */
    public const LEGACY_ROUND_TO_BUCKET = [
        '1' => '1',
        '2' => 'tweeners-3',
        '3' => 'tweeners-3',
        '4' => '4-5',
        '5-7' => '4-5',
        '8-10' => '6-plus',
        'post-10' => '6-plus',
        self::ROUND_COFFIN => '6-plus',
        '4+' => '4-5',
        '10+' => '6-plus',
    ];

    public static function normalizeRoundKey(string $roundKey): string
    {
        return match ($roundKey) {
            '4+' => '4',
            '10+' => 'post-10',
            default => $roundKey,
        };
    }

    public static function boardBucketKey(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);
        if (str_ends_with($normalized, '-'.self::SECTION_TARGETS) || str_ends_with($normalized, '-'.self::SECTION_PASS)) {
            return preg_replace('/-(targets|pass)$/', '', $normalized) ?? $normalized;
        }

        return self::LEGACY_ROUND_TO_BUCKET[$normalized] ?? $normalized;
    }

    public static function isPassRoundKey(string $roundKey): bool
    {
        return str_ends_with(self::normalizeRoundKey($roundKey), '-'.self::SECTION_PASS);
    }

    public static function isTargetsRoundKey(string $roundKey): bool
    {
        return str_ends_with(self::normalizeRoundKey($roundKey), '-'.self::SECTION_TARGETS);
    }

    public static function targetsRoundKeyForBucket(string $bucketKey): string
    {
        return $bucketKey.'-'.self::SECTION_TARGETS;
    }

    public static function passRoundKeyForBucket(string $bucketKey): string
    {
        return $bucketKey.'-'.self::SECTION_PASS;
    }

    public static function roundDisplayLabel(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);
        if (isset(self::LEGACY_ROUND_DISPLAY_LABELS[$normalized])) {
            return self::LEGACY_ROUND_DISPLAY_LABELS[$normalized];
        }

        return self::bucketDisplayLabel(self::boardBucketKey($normalized));
    }

    /** Column header label (e.g. "1ST ROUND / TARGETS"). */
    public static function roundColumnLabel(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);

        return self::ROUND_COLUMN_LABELS[$normalized] ?? $roundKey;
    }

    /** Picker button label for target columns. */
    public static function pickerRoundLabel(string $roundKey): string
    {
        $normalized = self::normalizeRoundKey($roundKey);

        return self::PICKER_ROUND_LABELS[$normalized] ?? self::roundColumnLabel($normalized);
    }

    public static function bucketDisplayLabel(string $bucketKey): string
    {
        return self::BUCKET_DISPLAY_LABELS[$bucketKey] ?? $bucketKey;
    }

    /** Profile header target-round chip. */
    public static function profileHeaderTargetRoundLabel(string $roundKey): string
    {
        return self::bucketDisplayLabel(self::boardBucketKey($roundKey));
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
        foreach (self::BOARD_ROUND_KEYS as $key) {
            $labels[$key] = self::roundColumnLabel($key);
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public static function pickerRoundLabels(): array
    {
        $labels = [];
        foreach (self::BOARD_PICKER_ROUND_KEYS as $key) {
            $labels[$key] = self::pickerRoundLabel($key);
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

    /**
     * Working-board conf/risk/round values shown on profiles and exports.
     *
     * @return array<int, self>
     */
    public static function masterEntriesIndexedByPlayerId(User $user): array
    {
        $indexed = [];

        foreach (self::query()
            ->where('user_id', $user->id)
            ->where('board_type', self::BOARD_MASTER)
            ->whereNotNull('player_id')
            ->get() as $entry) {
            $indexed[(int) $entry->player_id] = $entry;
        }

        return $indexed;
    }

    /**
     * @param  array<int, self>  $entriesByPlayerId
     */
    public static function masterEntryForPlayer(Player $player, array $entriesByPlayerId): ?self
    {
        return $entriesByPlayerId[$player->id] ?? null;
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
