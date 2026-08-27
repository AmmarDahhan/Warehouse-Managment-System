<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'phone'];

    public function departments()
    {
        return $this->hasMany(Department::class,'manager_id');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function installationReports()
    {
        return $this->hasMany(InstallationReport::class, 'approved_by');
    }

    public function scrapNotes()
    {
        return $this->hasMany(ScrapNote::class, 'approved_by');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}
