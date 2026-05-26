<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingBoardEntry extends Model
{
    /** @var list<string> */
    public const ROUND_KEYS = ['1', '2', '3', '4+', '10+'];

    /** @var list<string> */
    public const CONFIDENCE_OPTIONS = ['', 'HIGH', 'STRONG', 'AVERAGE', 'WEAK', 'TBD'];

    /** @var list<string> */
    public const RISK_OPTIONS = ['', 'LOW', 'MEDIUM', 'MED-HIGH', 'HIGH', 'EXTREME'];

    protected $fillable = [
        'user_id',
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
