<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->json('dataset_browse_settings')->nullable()->after('hs_profile_feed_slots');
        });
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropColumn('dataset_browse_settings');
        });
    }
};
