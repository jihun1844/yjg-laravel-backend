<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_route_id',
        'station',
        'bus_time'
    ];

    public function bus_route() {
        $this->belongsTo(busRoute::class);
    }
}