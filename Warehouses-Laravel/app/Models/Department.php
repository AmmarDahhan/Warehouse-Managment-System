<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name','manager_id','warehouse_id', 'description'];


    public function manager()
    {
        return $this->belongsTo(Manager::class,'manager_id');
    }
    public function warehouse()
    {
        // ⚠️ العلاقة الصحيحة: قسم ينتمي إلى مستودع (belongsTo)
        // لأنه هو من يحتوي على المفتاح الخارجي 'warehouse_id'
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
