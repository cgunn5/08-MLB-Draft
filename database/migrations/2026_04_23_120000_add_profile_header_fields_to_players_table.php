<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('bats', 8)->nullable()->after('position');
            $table->string('throws', 8)->nullable()->after('bats');
            $table->decimal('age', 4, 2)->nullable()->after('throws');
            $table->unsignedSmallInteger('personal_rank')->nullable()->after('source_ranks');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['bats', 'throws', 'age', 'personal_rank']);
        });
    }
};
