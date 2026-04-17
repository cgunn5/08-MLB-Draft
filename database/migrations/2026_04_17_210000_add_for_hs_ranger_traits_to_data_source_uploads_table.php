<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->boolean('for_hs_ranger_traits')->default(false)->after('heat_column_stats');
        });
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropColumn('for_hs_ranger_traits');
        });
    }
};
