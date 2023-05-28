<?php

namespace App\Http\Controllers\Branch;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\Table;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function list(Request $request)
    {
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $tables = Table::with('branch')
            ->where('branch_id', auth('branch')->user()->id)
            ->when($search!=null, function($query) use($key){
                foreach ($key as $value) {
                    $query->where('number', 'like', "%{$value}%");
                }
            })
            ->orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination());
        return view('branch-views.table.list', compact('tables','search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'number'  => [
                'required',
                Rule::unique('tables')->where(function ($query) use ($request) {
                    return $query->where(['number' => $request->number, 'branch_id' => auth('branch')->user()->id]);
                }),
            ],
            'capacity' => 'required|min:1|max:99',
        ], [
            'number.required' => translate('Table number is required!'),
            'number.unique' => translate('Table number is already exist in this branch!'),
            'capacity.required' => translate('Table capacity is required!'),
        ]);

        $table = new Table();
        $table->number = $request->number;
        $table->capacity = $request->capacity;
        $table->branch_id = auth('branch')->user()->id;
        $table->is_active = 1;
        $table->save();

        Toastr::success(translate('Table added successfully!'));
        return redirect()->route('branch.table.list');
    }

    public function status(Request $request)
    {
        $table = Table::find($request->id);
        $table->is_active = $request->status;
        $table->save();

        Toastr::success(translate('Table status updated!'));
        return back();
    }

    public function edit($id)
    {
        $table = Table::where(['id' => $id, 'branch_id' => auth('branch')->user()->id])->first();
        return view('branch-views.table.edit', compact('table'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'number'  => [
                'required',
                Rule::unique('tables')->where(function ($query) use ($request, $id) {
                    return $query->where(['number' => $request->number, 'branch_id' => auth('branch')->user()->id])
                        ->whereNotIn('id', [$id]);
                }),
            ],
            'capacity' => 'required|min:1|max:99',
        ], [
            'number.required' => translate('Table number is required!'),
            'number.unique' => translate('Table number is already exist in this branch!'),
            'capacity.required' => translate('Table capacity is required!'),
        ]);

        $table = Table::where(['id' => $id, 'branch_id' => auth('branch')->user()->id])->first();
        $table->number = $request->number;
        $table->capacity = $request->capacity;
        $table->update();

        Toastr::success(translate('Table updated successfully!'));
        return redirect()->route('branch.table.list');
    }

    public function delete(Request $request)
    {
        $table = Table::where(['id' => $request->id, 'branch_id' => auth('branch')->user()->id])->first();
        $table->delete();
        Toastr::success(translate('Table removed!'));
        return back();
    }

    public function index()
    {
        $tables = Table::with(['order'=> function ($q){
            $q->whereHas('table_order', function($q){
                $q->where('branch_table_token_is_expired', 0);
            });
        }])->where(['branch_id' => auth('branch')->user()->id, 'is_active' => '1'])->get()->toArray();
        return view('branch-views.table.index2', compact('tables'));
    }
}
