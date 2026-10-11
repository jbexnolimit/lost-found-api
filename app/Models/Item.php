<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'name',
        'brand',
        'model',
        'color',
        'description',
        'identifying_features',
        'serial_number',
        'incident_date',
        'incident_time',
        'location',
        'last_known_location',
        'additional_details',
        'preferred_contact_method',
        'contact_phone',
        'contact_email',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class)
            ->orderBy('sort_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ItemPhoto::class)
            ->where('is_primary', true);
    }

    public function lostMatches(): HasMany
    {
        return $this->hasMany(
            ItemMatch::class,
            'lost_item_id'
        );
    }

    public function foundMatches(): HasMany
    {
        return $this->hasMany(
            ItemMatch::class,
            'found_item_id'
        );
    }
}
