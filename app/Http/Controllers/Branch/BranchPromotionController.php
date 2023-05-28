<?php

namespace App\Http\Controllers\Branch;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\BranchPromotion;
use App\Model\BranchPromotionStatus;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class BranchPromotionController extends Controller
{
    public function create(Request $request)
    {
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $branch = Branch::where('id', auth('branch')->user()->id)->first();
        $promotions = BranchPromotion::where('branch_id', auth('branch')->user()->id)
            ->orderBy('id', 'Desc')
            ->when($search!=null, function($query) use($key){
                foreach ($key as $value) {
                    $query->orWhere('promotion_type', 'like', "%{$value}%")
                        ->orWhere('promotion_name', 'like', "%{$value}%");
                }
            })->orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination());
        return view('branch-views.branch_promotion.create', compact('search', 'promotions', 'branch'));
    }

    public function store(Request $request)
    {
        $promotion = new BranchPromotion();
        $promotion->branch_id = auth('branch')->user()->id;
        $promotion->promotion_type = $request->banner_type;;
        if ($request->video){
            $promotion->promotion_name = $request->video;
        }
        if ($request->image){
            $promotion->promotion_name = Helpers::upload('promotion/', 'png', $request->file('image'));
        }
        $promotion->save();

        Toastr::success(translate('Promotional campaign added successfully!'));
        return back();
    }

    public function edit(Request $request)
    {
        $promotion = BranchPromotion::find($request->id);
        return view('branch-views.branch_promotion.edit', compact('promotion'));
    }

    public function update(Request $request)
    {
        $promotion = BranchPromotion::find($request->id);
        $promotion->branch_id = auth('branch')->user()->id;
        $promotion->promotion_type = $request->banner_type;;
        if ($request->video){
            $promotion->promotion_name = $request->video;
        }
        if ($request->image){
            $promotion->promotion_name = $request->has('image') ? Helpers::update('promotion/', $promotion->image,'png', $request->file('image')):$promotion->image;

        }
        $promotion->update();

        Toastr::success(translate('Promotional campaign updated successfully!'));
        return redirect(url('branch/promotion/create'));
    }

    public function delete(Request $request)
    {
        $promotion = BranchPromotion::find($request->id);
        Helpers::delete('promotion/' . $promotion['promotion_name']);
        $promotion->delete();
        Toastr::success(translate('Promotional campaign removed!'));
        return back();
    }

    public function status(Request $request)
    {
        $branch = Branch::find($request->id);
        $branch->branch_promotion_status = $request->status;
        $branch->save();

        Toastr::success(translate('Promotion campaign status updated!'));
        return back();
    }
}
