<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->string('upload_kind', 32)->default('file');
            $table->foreignId('career_pg_source_upload_id')
                ->nullable()
                ->constrained('data_source_uploads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('data_source_uploads', function (Blueprint $table) {
            $table->dropForeign(['career_pg_source_upload_id']);
            $table->dropColumn(['upload_kind', 'career_pg_source_upload_id']);
        });
    }
};
