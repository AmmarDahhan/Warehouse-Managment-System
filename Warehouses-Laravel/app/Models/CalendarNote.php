<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_date',
        'noteContent',
        'user_id'
    ];

    protected $dates = ['note_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
