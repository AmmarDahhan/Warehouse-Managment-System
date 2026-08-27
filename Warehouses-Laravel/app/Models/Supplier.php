<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_info'
    ];

    public function entryNotes()
    {
        return $this->hasMany(EntryNote::class);
    }

    public function receivingNotes()
    {
        return $this->hasMany(ReceivingNote::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
} 