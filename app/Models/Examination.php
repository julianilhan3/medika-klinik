<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Examination extends Model
{
    protected $fillable = [
        'booking_id',
        'doctor_id',
        'complaint',
        'bp',
        'temp',
        'pulse',
        'height',
        'weight',
        'diagnosis',
        'note',
        'anamnesis',
        'icd',
        'therapy',
        'education',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }
}