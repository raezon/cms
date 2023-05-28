<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BranchPromotion extends Model
{
    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

//    public function branch_promotion_status(){
//        return $this->belongsTo(BranchPromotionStatus::class, 'branch_id', 'branch_id');
//    }

}
