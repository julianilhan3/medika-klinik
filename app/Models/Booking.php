<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
   protected $fillable = [
    'code',
    'patient_id',
    'doctor_id',
    'poli_id',
    'date',
    'time',
    'type',
    'status',
    'queue_no',
    'complaint',
    'rejection_reason',
    'checked_in_at',
    'started_at',
    'finished_at',
];

    protected $casts = [
        'date' => 'date',
        'checked_in_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }
}