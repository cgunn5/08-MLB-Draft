<?php

namespace App\Http\Controllers;

use App\Models\DataSourceUpload;
use Illuminate\Support\Collection;

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
}
