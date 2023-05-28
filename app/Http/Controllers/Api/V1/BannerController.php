<?php
namespace App\Http\Controllers\Api\V1;
use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use App\Model\Banner;
use Illuminate\Http\Request;
class BannerController extends Controller
{
    public function get_banners(){
        try {
            return response()->json(Banner::active()->get(), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
     public function get_ads_banners(){
       $banner= Banner::active()->get();
       foreach($banner as $object)
{
    $arrays[] ='/storage/app/public/banner/'.$object->image;
}
       return response()->json(['baners'=>$arrays,'status'=>'ok'], 200);
    }
}
