<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMatch extends Model
{
    protected $fillable = [
        'lost_item_id',
        'found_item_id',
        'match_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
        ];
    }

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'lost_item_id'
        );
    }

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'found_item_id'
        );
    }
}
