<?php

namespace App\Http\Controllers;

use App\Models\DataSourceUpload;
use Illuminate\Support\Collection;

class DataSourceController extends AbstractDataSourcePortalController
{
    protected function datasetPortal(): string
    {
        return DataSourceUpload::PORTAL_HS;
    }

    protected function routeGroup(): string
    {
        return 'data-sources';
    }

    protected function indexViewName(): string
    {
        return 'data-sources.index';
    }

    protected function profileFeedSlotsRequestKey(): string
    {
        return 'hs_profile_feed_slots';
    }

    protected function profileFeedSlotsColumn(): string
    {
        return 'hs_profile_feed_slots';
    }

    protected function profileFeedAssignmentsResponseKey(): string
    {
        return 'hs_profile_feed_assignments';
    }

    protected function profileFeedAssignmentsSlotFieldKey(): string
    {
        return 'hs_profile_feed_slots';
    }

    protected function resolvedProfileFeedSlotsForUpload(DataSourceUpload $upload, ?Collection $allUploads = null): array
    {
        return $upload->resolvedHsProfileFeedSlotsForUi($allUploads);
    }
}
