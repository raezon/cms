<?php

namespace App\Http\Controllers\Admin;

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
        $branches = Branch::orderBy('id', 'DESC')->where(['status' => 1])->get();
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $promotions = BranchPromotion::with('branch')
            ->when($search!=null, function($query) use($key){
            foreach ($key as $value) {
                $query->where('branch_id', 'like', "%{$value}%")
                ->orWhere('promotion_type', 'like', "%{$value}%")
                ->orWhere('promotion_name', 'like', "%{$value}%");
            }
        })->orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination());
        return view('admin-views.branch_promotion.create', compact('branches', 'search', 'promotions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required',
        ], [
            'branch_id.required' => translate('Branch select is required!'),
        ]);

        $promotion = new BranchPromotion();
        $promotion->branch_id = $request->branch_id;
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
        $branches = Branch::orderBy('id', 'DESC')->get();
        return view('admin-views.branch_promotion.edit', compact('promotion', 'branches'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'branch_id' => 'required',
        ], [
            'branch_id.required' => translate('Branch select is required!'),
        ]);

        $promotion = BranchPromotion::find($request->id);
        $promotion->branch_id = $request->branch_id;
        $promotion->promotion_type = $request->banner_type;;
        if ($request->video){
            $promotion->promotion_name = $request->video;
        }
        if ($request->image){
            $promotion->promotion_name = $request->has('image') ? Helpers::update('promotion/', $promotion->image,'png', $request->file('image')):$promotion->image;

        }
        $promotion->update();

        Toastr::success(translate('Promotional campaign updated successfully!'));
        return back();
    }

    public function delete(Request $request)
    {
        $promotion = BranchPromotion::find($request->id);
        Helpers::delete('promotion/' . $promotion['promotion_name']);
        $promotion->delete();
        Toastr::success(translate('Promotional campaign removed!'));
        return back();
    }

    public function branch_wise_list(Request $request)
    {
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $branch = Branch::where('id', $request->id)->first();
        $promotions = BranchPromotion::where('branch_id', $request->id)
        ->with('branch')
            ->when($search!=null, function($query) use($key){
                foreach ($key as $value) {
                    $query->where('branch_id', 'like', "%{$value}%")
                        ->orWhere('promotion_type', 'like', "%{$value}%")
                        ->orWhere('promotion_name', 'like', "%{$value}%");
                }
            })->paginate(Helpers::getPagination());
        return view('admin-views.branch_promotion.branch_wise_list', compact( 'search', 'promotions', 'branch'));
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
