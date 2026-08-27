<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapNote extends Model
{
    protected $fillable = [
        'created_by',
        'approved_by',
        'serial_number',
        'reason',
        'date',
        'notes',
        'status',
        'rejection_reason'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function materials()
    {
        return $this->hasMany(ScrappedMaterial::class, 'scrap_note_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Manager::class, 'approved_by');
    }

}
