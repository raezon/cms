<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Branch extends Authenticatable
{
    use Notifiable;

    protected $casts = [
        'coverage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function branch_promotion(){
        return $this->hasMany(BranchPromotion::class);
    }

    public function table(){
        return $this->hasMany(Table::class, 'branch_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}
