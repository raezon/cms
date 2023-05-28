<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $table = 'tables';

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function order(){
        return $this->hasMany(Order::class, 'table_id', 'id');
    }

    public function table_order(){
        return $this->hasMany(TableOrder::class, 'table_id', 'id');
    }
}
