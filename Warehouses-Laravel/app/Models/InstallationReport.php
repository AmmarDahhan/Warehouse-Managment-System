<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationReport extends Model
{
    protected $fillable = [
        'created_by',
        'status',
        'manager_id',
        'serial_number',
        'location',
        'type',
        'date',
        'notes'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function createdBy()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    public function materials()
    {
        return $this->hasMany(InstallationMaterial::class);
    }
}
