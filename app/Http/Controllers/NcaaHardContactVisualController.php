<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNcaaHardContactVisualRequest;
use App\Models\NcaaPlayerHardContactVisual;
use App\Models\Player;
use App\Support\NcaaHardContactVisualLibraryTab;
use App\Support\NcaaHardContactVisualStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NcaaHardContactVisualController extends Controller
{
    public function update(UpdateNcaaHardContactVisualRequest $request, Player $player): RedirectResponse
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        DB::transaction(function () use ($request, $user, $player): void {
            /** @var NcaaPlayerHardContactVisual $visual */
            $visual = NcaaPlayerHardContactVisual::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'player_id' => $player->id,
                ],
            );

            if ($request->hasFile('plate_heatmap')) {
                $visual->deletePlateHeatmapFile();
                $stored = NcaaHardContactVisualStorage::store(
                    $user->id,
                    $player->id,
                    NcaaHardContactVisualStorage::TYPE_PLATE,
                    $request->file('plate_heatmap'),
                );
                $visual->plate_heatmap_disk = $stored['disk'];
                $visual->plate_heatmap_path = $stored['path'];
            }

            if ($request->hasFile('zone_pitch_map')) {
                $visual->deleteZonePitchMapFile();
                $stored = NcaaHardContactVisualStorage::store(
                    $user->id,
                    $player->id,
                    NcaaHardContactVisualStorage::TYPE_ZONE,
                    $request->file('zone_pitch_map'),
                );
                $visual->zone_pitch_map_disk = $stored['disk'];
                $visual->zone_pitch_map_path = $stored['path'];
            }

            $visual->save();

            if (! $visual->hasPlateHeatmap() && ! $visual->hasZonePitchMap()) {
                $visual->delete();
            }
        });

        return redirect()
            ->route('ncaa-data-sources.index', ['dataset' => NcaaHardContactVisualLibraryTab::TAB_ID])
            ->with('status', __('Hard contact visuals saved for :player.', [
                'player' => strtoupper($player->last_name).', '.strtoupper($player->first_name),
            ]));
    }

    public function destroy(Player $player, string $type): RedirectResponse
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $user = auth()->user();
        if ($user === null || ! $user->is_admin) {
            abort(403);
        }

        $type = NcaaHardContactVisualStorage::normalizeType($type);
        if (! NcaaHardContactVisualStorage::isValidType($type)) {
            abort(404);
        }

        $visual = NcaaPlayerHardContactVisual::forUserPlayer($user, $player);
        if ($visual === null) {
            return redirect()
                ->route('ncaa-data-sources.index', ['dataset' => NcaaHardContactVisualLibraryTab::TAB_ID])
                ->with('status', __('No hard contact visuals found for that player.'));
        }

        if ($type === NcaaHardContactVisualStorage::TYPE_PLATE) {
            $visual->deletePlateHeatmapFile();
        } else {
            $visual->deleteZonePitchMapFile();
        }

        if (! $visual->hasPlateHeatmap() && ! $visual->hasZonePitchMap()) {
            $visual->delete();
        } else {
            $visual->save();
        }

        return redirect()
            ->route('ncaa-data-sources.index', ['dataset' => NcaaHardContactVisualLibraryTab::TAB_ID])
            ->with('status', __('Hard contact visual removed.'));
    }

    public function show(Player $player, string $type): Response|StreamedResponse
    {
        if ($player->player_pool !== 'ncaa') {
            abort(404);
        }

        $type = NcaaHardContactVisualStorage::normalizeType($type);
        if (! NcaaHardContactVisualStorage::isValidType($type)) {
            abort(404);
        }

        $user = auth()->user()?->dataOwner();
        if ($user === null) {
            abort(403);
        }

        $visual = NcaaPlayerHardContactVisual::forUserPlayer($user, $player);
        if ($visual === null) {
            abort(404);
        }

        if ($type === NcaaHardContactVisualStorage::TYPE_PLATE) {
            $disk = $visual->plate_heatmap_disk;
            $path = $visual->plate_heatmap_path;
        } else {
            $disk = $visual->zone_pitch_map_disk;
            $path = $visual->zone_pitch_map_path;
        }

        if (! NcaaHardContactVisualStorage::exists($disk, $path)) {
            abort(404);
        }

        $stream = NcaaHardContactVisualStorage::readStream($disk, $path);
        if ($stream === null) {
            abort(404);
        }

        $mime = NcaaHardContactVisualStorage::mimeType($disk, $path) ?? 'image/png';

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
