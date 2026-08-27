<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'name',
        'email',
        'phone',
        'password',
        'job_title',
        'gender',
        'address',
        'birth_date',
        'facebook_url',
        'instagram_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class,'department_id');
    }


    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by');
    }

    public function custodies()
    {
        return $this->hasMany(Custody::class);
    }

    public function notes()
    {
        return $this->hasMany(CalendarNote::class);
    }

    public function ownedRooms()
    {
        return $this->hasMany(Room::class, 'user_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}
