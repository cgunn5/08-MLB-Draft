<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_source_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_filename');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->json('header_row');
            $table->unsignedInteger('row_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_source_uploads');
    }
};
