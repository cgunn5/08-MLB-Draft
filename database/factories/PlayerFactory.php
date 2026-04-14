<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'master_take' => null,
            'player_pool' => 'ncaa',
            'school' => null,
            'position' => null,
            'aggregate_rank' => null,
            'aggregate_score' => null,
            'source_ranks' => null,
            'grade_role' => null,
            'grade_perf' => null,
            'grade_approach' => null,
            'grade_contact' => null,
            'grade_damage' => null,
            'grade_adj' => null,
            'grade_swing' => null,
            'note_performance' => null,
            'note_engine' => null,
            'note_approach_miss' => null,
            'note_left_right' => null,
            'note_pitch_coverage' => null,
            'note_swing' => null,
        ];
    }
}
