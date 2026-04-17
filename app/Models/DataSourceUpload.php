<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'original_filename',
    'disk',
    'path',
    'header_row',
    'row_count',
    'column_order',
    'heat_rules',
    'heat_column_stats',
    'for_hs_ranger_traits',
])]
class DataSourceUpload extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_row' => 'array',
            'column_order' => 'array',
            'heat_rules' => 'array',
            'heat_column_stats' => 'array',
            'for_hs_ranger_traits' => 'boolean',
        ];
    }
}
