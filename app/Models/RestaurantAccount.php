<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account',
        'bank_name',
        'name',
    ];
}
