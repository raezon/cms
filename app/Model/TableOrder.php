<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TableOrder extends Model
{
    protected $table = 'table_orders';

    public function order()
    {
        return $this->hasMany(Order::class, 'table_order_id', 'id');
    }
}

