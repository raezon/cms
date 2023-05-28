<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivery_address' => 'array'
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id')->withCount('orders');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id')->withCount('orders');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withCount('orders');
    }

    public function delivery_address()
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function table_order()
    {
        return $this->belongsTo(TableOrder::class, 'table_order_id', 'id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=' , 'pos');
    }

    public function scopeDineIn($query)
    {
        return $query->where('order_type', '=' , 'dine_in');
    }


    public function scopeNotDineIn($query)
    {
        return $query->where('order_type', '!=' , 'dine_in');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=' , 'pos');
    }

    public function scopeSchedule($query)
    {
        return $query->whereDate('delivery_date','>',\Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeNotSchedule($query)
    {
        return $query->whereDate('delivery_date','<=',\Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeEarningReport($query)
    {
        return $query->whereIn('order_status', ['delivered', 'completed']);
    }
}
