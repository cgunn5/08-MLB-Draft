<?php

namespace App\Models;

use App\Support\NcaaHardContactVisualStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'player_id',
    'plate_heatmap_disk',
    'plate_heatmap_path',
    'zone_pitch_map_disk',
    'zone_pitch_map_path',
])]
class NcaaPlayerHardContactVisual extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function hasPlateHeatmap(): bool
    {
        return NcaaHardContactVisualStorage::exists($this->plate_heatmap_disk, $this->plate_heatmap_path);
    }

    public function hasZonePitchMap(): bool
    {
        return NcaaHardContactVisualStorage::exists($this->zone_pitch_map_disk, $this->zone_pitch_map_path);
    }

    public function plateHeatmapUrl(): ?string
    {
        if (! $this->hasPlateHeatmap()) {
            return null;
        }

        return route('ncaa.players.hard-contact.show', [
            'player' => $this->player_id,
            'type' => NcaaHardContactVisualStorage::TYPE_PLATE,
            'v' => $this->updated_at?->timestamp ?? 0,
        ]);
    }

    public function zonePitchMapUrl(): ?string
    {
        if (! $this->hasZonePitchMap()) {
            return null;
        }

        return route('ncaa.players.hard-contact.show', [
            'player' => $this->player_id,
            'type' => NcaaHardContactVisualStorage::TYPE_ZONE,
            'v' => $this->updated_at?->timestamp ?? 0,
        ]);
    }

    /**
     * @return array{plate_url: ?string, zone_url: ?string}|null
     */
    public function toProfilePayload(): ?array
    {
        $plateUrl = $this->plateHeatmapUrl();
        $zoneUrl = $this->zonePitchMapUrl();

        if ($plateUrl === null && $zoneUrl === null) {
            return null;
        }

        return [
            'plate_url' => $plateUrl,
            'zone_url' => $zoneUrl,
        ];
    }

    public function deletePlateHeatmapFile(): void
    {
        NcaaHardContactVisualStorage::delete($this->plate_heatmap_disk, $this->plate_heatmap_path);
        $this->plate_heatmap_disk = null;
        $this->plate_heatmap_path = null;
    }

    public function deleteZonePitchMapFile(): void
    {
        NcaaHardContactVisualStorage::delete($this->zone_pitch_map_disk, $this->zone_pitch_map_path);
        $this->zone_pitch_map_disk = null;
        $this->zone_pitch_map_path = null;
    }

    public function deleteAllFiles(): void
    {
        $this->deletePlateHeatmapFile();
        $this->deleteZonePitchMapFile();
    }

    public static function forUserPlayer(User $user, Player $player): ?self
    {
        return self::query()
            ->where('user_id', $user->id)
            ->where('player_id', $player->id)
            ->first();
    }
}
