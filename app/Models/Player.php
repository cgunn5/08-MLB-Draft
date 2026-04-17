<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'first_name',
    'last_name',
    'master_take',
    'player_pool',
    'school',
    'position',
    'aggregate_rank',
    'aggregate_score',
    'source_ranks',
    'grade_role',
    'grade_perf',
    'grade_approach',
    'grade_contact',
    'grade_damage',
    'grade_adj',
    'grade_swing',
    'note_performance',
    'note_engine',
    'note_approach_miss',
    'note_left_right',
    'note_pitch_coverage',
    'note_swing',
])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_ranks' => 'array',
            'aggregate_score' => 'float',
        ];
    }

    /**
     * Metrics table: UI label => grade/value attribute.
     *
     * @return array<string, string>
     */
    public static function gradeRowDefinitions(): array
    {
        return [
            'ROLE' => 'grade_role',
            'PERF' => 'grade_perf',
            'APPROACH' => 'grade_approach',
            'CONTACT' => 'grade_contact',
            'DAMAGE' => 'grade_damage',
            'ADJ' => 'grade_adj',
            'SWING' => 'grade_swing',
        ];
    }

    /**
     * HS profile grades table (no ADJ row; matches board layout).
     *
     * @return array<string, string>
     */
    public static function gradeRowDefinitionsHs(): array
    {
        return [
            'ROLE' => 'grade_role',
            'PERF' => 'grade_perf',
            'APPROACH' => 'grade_approach',
            'CONTACT' => 'grade_contact',
            'DAMAGE' => 'grade_damage',
            'SWING' => 'grade_swing',
        ];
    }

    /**
     * In-memory player for HS/NCAA dashboard landing: full profile shell with no persisted row.
     */
    public static function profilePlaceholder(string $playerPool): self
    {
        return new self([
            'player_pool' => $playerPool,
            'first_name' => '',
            'last_name' => '',
        ]);
    }

    /**
     * One-line roster / aggregate summary for under the name selector (list-backed fields).
     */
    public function listSummaryLine(): ?string
    {
        $parts = array_values(array_filter([
            filled($this->school) ? (string) $this->school : null,
            filled($this->position) ? (string) $this->position : null,
            $this->aggregate_rank !== null ? 'RK '.$this->aggregate_rank : null,
            $this->aggregate_score !== null ? 'AGG '.number_format((float) $this->aggregate_score, 1) : null,
        ]));

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    /**
     * Two-line summary under the player combobox: school/position, then RK/AGG.
     *
     * @return array{school_position: ?string, rank_agg: ?string}
     */
    public function listSummaryLinesForSelect(): array
    {
        $schoolPositionParts = array_values(array_filter([
            filled($this->school) ? (string) $this->school : null,
            filled($this->position) ? (string) $this->position : null,
        ]));
        $rankAggParts = array_values(array_filter([
            $this->aggregate_rank !== null ? 'RK '.$this->aggregate_rank : null,
            $this->aggregate_score !== null ? 'AGG '.number_format((float) $this->aggregate_score, 1) : null,
        ]));

        return [
            'school_position' => $schoolPositionParts !== [] ? implode(' · ', $schoolPositionParts) : null,
            'rank_agg' => $rankAggParts !== [] ? implode(' · ', $rankAggParts) : null,
        ];
    }

    /**
     * Cell text for the profile grades table (list data fills ROLE when grade is empty).
     */
    public function gradeCellDisplay(string $attribute): string
    {
        $value = $this->{$attribute};

        if (filled($value)) {
            return (string) $value;
        }

        if ($attribute === 'grade_role' && filled($this->position)) {
            return (string) $this->position;
        }

        return '#N/A';
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeNcaa(Builder $query): Builder
    {
        return $query->where('player_pool', 'ncaa');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeHs(Builder $query): Builder
    {
        return $query->where('player_pool', 'hs');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('last_name')->orderBy('first_name');
    }

    /**
     * Order for the master player list (aggregate rank, then name).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOrderedForPlayerList(Builder $query): Builder
    {
        return $query
            ->orderByRaw('aggregate_rank IS NULL')
            ->orderBy('aggregate_rank')
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
