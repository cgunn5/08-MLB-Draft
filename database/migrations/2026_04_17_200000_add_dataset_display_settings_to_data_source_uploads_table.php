<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->json('column_order')->nullable()->after('row_count');
            $table->json('heat_rules')->nullable()->after('column_order');
            $table->json('heat_column_stats')->nullable()->after('heat_rules');
        });
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropColumn(['column_order', 'heat_rules', 'heat_column_stats']);
        });
    }
};
