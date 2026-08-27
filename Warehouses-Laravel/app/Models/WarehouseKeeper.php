<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class WarehouseKeeper extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'warehouse_id',
        'gender',
        'address',
        'birth_date',
        'facebook_url',
        'instagram_url',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'warehouse_keeper_id');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function entryNotes()
    {
        return $this->hasMany(EntryNote::class, 'created_by');
    }

    public function receivingNotes()
    {
        return $this->hasMany(ReceivingNote::class, 'created_by');
    }

    public function exitNotes()
    {
        return $this->hasMany(ExitNote::class, 'created_by');
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'created_by');
    }

    public function installationReports()
    {
        return $this->hasMany(InstallationReport::class, 'created_by');
    }

    public function scrapNotes()
    {
        return $this->hasMany(ScrapNote::class, 'created_by');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}
