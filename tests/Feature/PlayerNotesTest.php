<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use App\Support\PlayerNoteFieldKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_note_section(): void
    {
        $player = Player::factory()->create(['player_pool' => 'ncaa']);

        $this->patch(route('notes.update-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'master_take',
            'value' => 'Take',
        ])->assertRedirect(route('login'));
    }

    public function test_guest_cannot_bulk_update_notes(): void
    {
        $player = Player::factory()->create(['player_pool' => 'ncaa']);
        $values = $this->noteValuesPayload($player, ['master_take' => 'X']);

        $this->patch(route('notes.update-all'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'values' => $values,
        ])->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_note_section(): void
    {
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'master_take' => 'X']);

        $this->delete(route('notes.destroy-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'master_take',
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_single_ncaa_section(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'master_take' => null,
            'note_performance' => 'Keep',
        ]);

        $response = $this->actingAs($user)->patch(route('notes.update-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'master_take',
            'value' => 'Master text',
        ]);

        $response->assertRedirect(route('notes.index', ['player' => $player->id]));
        $response->assertSessionHas('status');

        $player->refresh();
        $this->assertSame('Master text', $player->master_take);
        $this->assertSame('Keep', $player->note_performance);
    }

    public function test_section_update_can_clear_field_with_empty_value(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'master_take' => 'Was',
        ]);

        $this->actingAs($user)->patch(route('notes.update-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'master_take',
            'value' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($player->fresh()->master_take);
    }

    public function test_hs_section_update_does_not_touch_note_left_right(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'note_left_right' => 'Keep me',
        ]);

        $this->actingAs($user)->patch(route('notes.update-section'), [
            'player_pool' => 'hs',
            'player_id' => $player->id,
            'field' => 'master_take',
            'value' => 'HS take',
        ])->assertSessionHasNoErrors();

        $player->refresh();
        $this->assertSame('HS take', $player->master_take);
        $this->assertSame('Keep me', $player->note_left_right);
    }

    public function test_notes_index_redirects_when_player_query_is_unknown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notes.index', ['player' => 999999]))
            ->assertRedirect(route('notes.index'));
    }

    public function test_bulk_update_saves_all_sections_and_grades(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'master_take' => null,
            'note_performance' => 'Old perf',
            'grade_perf' => null,
            'grade_role' => null,
        ]);

        $allowed = PlayerNoteFieldKeys::forPool('hs');
        $values = [];
        foreach ($allowed as $field) {
            $values[$field] = $player->{$field} ?? '';
        }
        $values['master_take'] = 'New take';
        $values['note_performance'] = 'New perf';

        $grades = [];
        foreach ($allowed as $field) {
            $grades[$field] = $field === 'master_take' ? 5.5 : 3;
        }

        $this->actingAs($user)
            ->patch(route('notes.update-all'), [
                'player_pool' => 'hs',
                'player_id' => $player->id,
                'values' => $values,
                'grades' => $grades,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('notes.index', ['player' => $player->id]));

        $player->refresh();
        $this->assertSame('New take', $player->master_take);
        $this->assertSame('New perf', $player->note_performance);
        $this->assertSame('5.5', $player->grade_role);
        $this->assertSame('3', $player->grade_perf);
    }

    public function test_rejects_section_update_when_player_id_does_not_match_pool(): void
    {
        $user = User::factory()->create();
        $hsPlayer = Player::factory()->create(['player_pool' => 'hs']);

        $this->actingAs($user)
            ->patch(route('notes.update-section'), [
                'player_pool' => 'ncaa',
                'player_id' => $hsPlayer->id,
                'field' => 'master_take',
                'value' => 'X',
            ])
            ->assertSessionHasErrors('player_id');
    }

    public function test_rejects_section_update_for_field_not_in_pool(): void
    {
        $user = User::factory()->create();
        $hsPlayer = Player::factory()->create(['player_pool' => 'hs']);

        $this->actingAs($user)
            ->patch(route('notes.update-section'), [
                'player_pool' => 'hs',
                'player_id' => $hsPlayer->id,
                'field' => 'note_left_right',
                'value' => 'Nope',
            ])
            ->assertSessionHasErrors('field');
    }

    public function test_authenticated_user_can_delete_single_section(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'master_take' => 'M',
            'note_performance' => 'P',
        ]);

        $response = $this->actingAs($user)->delete(route('notes.destroy-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'master_take',
        ]);

        $response->assertRedirect(route('notes.index', ['player' => $player->id]));
        $response->assertSessionHas('status');

        $player->refresh();
        $this->assertNull($player->master_take);
        $this->assertSame('P', $player->note_performance);
    }

    public function test_section_update_can_set_matching_profile_grade(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'note_performance' => 'Solid',
            'grade_perf' => null,
        ]);

        $this->actingAs($user)->patch(route('notes.update-section'), [
            'player_pool' => 'ncaa',
            'player_id' => $player->id,
            'field' => 'note_performance',
            'value' => 'Solid',
            'grade' => 4.5,
        ])->assertSessionHasNoErrors();

        $player->refresh();
        $this->assertSame('4.5', $player->grade_perf);
    }

    public function test_master_take_grade_saves_to_grade_role(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'master_take' => 'Corner bat',
            'grade_role' => null,
        ]);

        $this->actingAs($user)->patch(route('notes.update-section'), [
            'player_pool' => 'hs',
            'player_id' => $player->id,
            'field' => 'master_take',
            'value' => 'Corner bat',
            'grade' => 6,
        ])->assertSessionHasNoErrors();

        $this->assertSame('6', $player->fresh()->grade_role);
    }

    public function test_rejects_grade_below_take_scale_for_master_take(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'master_take' => 'X']);

        $this->actingAs($user)
            ->patch(route('notes.update-section'), [
                'player_pool' => 'ncaa',
                'player_id' => $player->id,
                'field' => 'master_take',
                'value' => 'X',
                'grade' => 1,
            ])
            ->assertSessionHasErrors('grade');
    }

    public function test_rejects_grade_above_take_scale_for_trait_note(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'note_swing' => 'Short']);

        $this->actingAs($user)
            ->patch(route('notes.update-section'), [
                'player_pool' => 'ncaa',
                'player_id' => $player->id,
                'field' => 'note_swing',
                'value' => 'Short',
                'grade' => 8,
            ])
            ->assertSessionHasErrors('grade');
    }

    public function test_rejects_grade_not_in_half_point_steps(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'note_swing' => 'Short']);

        $this->actingAs($user)
            ->patch(route('notes.update-section'), [
                'player_pool' => 'ncaa',
                'player_id' => $player->id,
                'field' => 'note_swing',
                'value' => 'Short',
                'grade' => 4.25,
            ])
            ->assertSessionHasErrors('grade');
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    private function noteValuesPayload(Player $player, array $overrides = []): array
    {
        $allowed = PlayerNoteFieldKeys::forPool($player->player_pool);
        $values = [];
        foreach ($allowed as $field) {
            $values[$field] = $player->{$field} ?? '';
        }

        return array_merge($values, $overrides);
    }
}
