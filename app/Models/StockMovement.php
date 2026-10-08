<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'medicine_id',
        'qty',
        'expiry',
        'batch',
        'type',
        'note',
    ];

    protected $casts = [
        'expiry' => 'date',
        'qty' => 'integer',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}