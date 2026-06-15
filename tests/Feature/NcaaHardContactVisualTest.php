<?php

namespace Tests\Feature;

use App\Models\NcaaPlayerHardContactVisual;
use App\Models\Player;
use App\Models\User;
use App\Support\NcaaHardContactVisualStorage;
use App\Support\NcaaRangerTraitsSheetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NcaaHardContactVisualTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_hard_contact_visuals_for_ncaa_player(): void
    {
        Storage::fake('local');

        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa']);

        $response = $this->actingAs($user)->post(
            route('ncaa-data-sources.hard-contact-visuals.update', $player),
            [
                'plate_heatmap' => UploadedFile::fake()->image('plate.png', 128, 164),
                'zone_pitch_map' => UploadedFile::fake()->image('zone.png', 128, 164),
            ],
        );

        $response->assertRedirect(route('ncaa-data-sources.index', ['dataset' => \App\Support\NcaaHardContactVisualLibraryTab::TAB_ID]));
        $response->assertSessionHas('status');

        $visual = NcaaPlayerHardContactVisual::forUserPlayer($user, $player);
        $this->assertNotNull($visual);
        $this->assertTrue($visual->hasPlateHeatmap());
        $this->assertTrue($visual->hasZonePitchMap());
        $this->assertNotNull($visual->plateHeatmapUrl());
        $this->assertNotNull($visual->zonePitchMapUrl());
    }

    public function test_authenticated_user_can_view_hard_contact_image_for_player(): void
    {
        Storage::fake('local');

        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa']);

        $stored = NcaaHardContactVisualStorage::store(
            $user->id,
            $player->id,
            NcaaHardContactVisualStorage::TYPE_PLATE,
            UploadedFile::fake()->image('plate.png', 128, 164),
        );

        NcaaPlayerHardContactVisual::query()->create([
            'user_id' => $user->id,
            'player_id' => $player->id,
            'plate_heatmap_disk' => $stored['disk'],
            'plate_heatmap_path' => $stored['path'],
        ]);

        $response = $this->actingAs($user)->get(
            route('ncaa.players.hard-contact.show', ['player' => $player, 'type' => 'plate']),
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
    }

    public function test_resolver_includes_hard_contact_urls_on_ncaa_profile(): void
    {
        Storage::fake('local');

        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'ncaa',
            'first_name' => 'Tyson',
            'last_name' => 'Leblanc',
        ]);

        $plate = NcaaHardContactVisualStorage::store(
            $user->id,
            $player->id,
            NcaaHardContactVisualStorage::TYPE_PLATE,
            UploadedFile::fake()->image('plate.png', 128, 164),
        );
        $zone = NcaaHardContactVisualStorage::store(
            $user->id,
            $player->id,
            NcaaHardContactVisualStorage::TYPE_ZONE,
            UploadedFile::fake()->image('zone.png', 128, 164),
        );

        NcaaPlayerHardContactVisual::query()->create([
            'user_id' => $user->id,
            'player_id' => $player->id,
            'plate_heatmap_disk' => $plate['disk'],
            'plate_heatmap_path' => $plate['path'],
            'zone_pitch_map_disk' => $zone['disk'],
            'zone_pitch_map_path' => $zone['path'],
        ]);

        $sheet = app(NcaaRangerTraitsSheetResolver::class)->resolve($player, $user, null);

        $this->assertIsArray($sheet['hard_contact'] ?? null);
        $this->assertNotNull($sheet['hard_contact']['plate_url'] ?? null);
        $this->assertNotNull($sheet['hard_contact']['zone_url'] ?? null);
    }

    public function test_admin_can_remove_single_hard_contact_visual(): void
    {
        Storage::fake('local');

        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa']);

        $plate = NcaaHardContactVisualStorage::store(
            $user->id,
            $player->id,
            NcaaHardContactVisualStorage::TYPE_PLATE,
            UploadedFile::fake()->image('plate.png', 128, 164),
        );
        $zone = NcaaHardContactVisualStorage::store(
            $user->id,
            $player->id,
            NcaaHardContactVisualStorage::TYPE_ZONE,
            UploadedFile::fake()->image('zone.png', 128, 164),
        );

        NcaaPlayerHardContactVisual::query()->create([
            'user_id' => $user->id,
            'player_id' => $player->id,
            'plate_heatmap_disk' => $plate['disk'],
            'plate_heatmap_path' => $plate['path'],
            'zone_pitch_map_disk' => $zone['disk'],
            'zone_pitch_map_path' => $zone['path'],
        ]);

        $response = $this->actingAs($user)->delete(
            route('ncaa-data-sources.hard-contact-visuals.destroy', [$player, 'plate']),
        );

        $response->assertRedirect(route('ncaa-data-sources.index', ['dataset' => \App\Support\NcaaHardContactVisualLibraryTab::TAB_ID]));

        $visual = NcaaPlayerHardContactVisual::forUserPlayer($user, $player);
        $this->assertNotNull($visual);
        $this->assertFalse($visual->hasPlateHeatmap());
        $this->assertTrue($visual->hasZonePitchMap());
    }

    public function test_non_admin_cannot_upload_hard_contact_visuals(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $player = Player::factory()->create(['player_pool' => 'ncaa']);

        $response = $this->actingAs($user)->post(
            route('ncaa-data-sources.hard-contact-visuals.update', $player),
            [
                'plate_heatmap' => UploadedFile::fake()->image('plate.png', 128, 164),
            ],
        );

        $response->assertForbidden();
    }
}
