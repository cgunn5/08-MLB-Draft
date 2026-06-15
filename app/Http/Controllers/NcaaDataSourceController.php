<?php

namespace App\Http\Controllers;

use App\Support\NcaaHardContactVisualLibraryTab;
use App\Models\DataSourceUpload;
use App\Models\NcaaPlayerHardContactVisual;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NcaaDataSourceController extends AbstractDataSourcePortalController
{
    protected function datasetPortal(): string
    {
        return DataSourceUpload::PORTAL_NCAA;
    }

    protected function routeGroup(): string
    {
        return 'ncaa-data-sources';
    }

    protected function indexViewName(): string
    {
        return 'ncaa-data-sources.index';
    }

    protected function profileFeedSlotsRequestKey(): string
    {
        return 'ncaa_profile_feed_slots';
    }

    protected function profileFeedSlotsColumn(): string
    {
        return 'ncaa_profile_feed_slots';
    }

    protected function profileFeedAssignmentsResponseKey(): string
    {
        return 'ncaa_profile_feed_assignments';
    }

    protected function profileFeedAssignmentsSlotFieldKey(): string
    {
        return 'ncaa_profile_feed_slots';
    }

    protected function resolvedProfileFeedSlotsForUpload(DataSourceUpload $upload, ?Collection $allUploads = null): array
    {
        return $upload->resolvedNcaaProfileFeedSlotsForUi();
    }

    public function index(): View
    {
        $view = parent::index();
        /** @var \Illuminate\Support\Collection<int, DataSourceUpload> $uploads */
        $uploads = $view->getData()['uploads'];
        $initialActiveId = $view->getData()['initialActiveId'];
        $tabId = NcaaHardContactVisualLibraryTab::TAB_ID;
        $queryDataset = request()->query('dataset');

        if ($queryDataset === $tabId) {
            $initialActiveId = $tabId;
        } elseif ($initialActiveId === null) {
            $initialActiveId = $tabId;
        }

        return $view->with([
            'initialActiveId' => $initialActiveId,
            'hardContactVisualsTabId' => $tabId,
            'ncaaPlayers' => Player::query()->ncaa()->orderedByName()->get(),
            'hardContactVisualsByPlayerId' => NcaaPlayerHardContactVisual::query()
                ->where('user_id', auth()->id())
                ->get()
                ->keyBy('player_id'),
        ]);
    }
}
