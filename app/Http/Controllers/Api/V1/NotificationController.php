<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function get_notifications(){
        try {
          $notification= Notification::active()->get();
            return response()->json(['notification'=>$notification,"status"=>"OK"], 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
