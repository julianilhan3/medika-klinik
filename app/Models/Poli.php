<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;

    protected $table = 'poli';

    protected $fillable = [
        'name',
    ];

    public function doctors()
    {
        return $this->hasMany(User::class, 'poli_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'poli_id');
    }
}