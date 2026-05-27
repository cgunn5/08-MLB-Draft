<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->string('pitch_type_feed', 8)->nullable()->after('dataset_browse_settings');
        });
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropColumn('pitch_type_feed');
        });
    }
};
