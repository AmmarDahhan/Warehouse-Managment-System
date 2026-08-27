<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'building_id',
        'user_id',
        'room_code',
        'description'
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function custodies()
    {
        return $this->hasMany(Custody::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
