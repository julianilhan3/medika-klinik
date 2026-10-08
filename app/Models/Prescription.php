<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = [
        'code',
        'examination_id',
        'status',
        'pharmacy_no',
        'note',
        'handed_by',
        'handed_at',
    ];

    protected $casts = [
        'handed_at' => 'datetime',
    ];

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function handedBy()
    {
        return $this->belongsTo(User::class, 'handed_by');
    }
}