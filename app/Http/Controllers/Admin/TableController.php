<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\Order;
use App\Model\Table;
use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class TableController extends Controller
{
    public function list(Request $request)
    {
        $branches = Branch::orderBy('id', 'DESC')->get();
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $tables = Table::with('branch')
        ->when($search!=null, function($query) use($key){
                foreach ($key as $value) {
                    $query->where('number', 'like', "%{$value}%");
                }
            })
            ->orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination());
        return view('admin-views.table.list', compact('tables','search', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'number'  => [
                'required',
                Rule::unique('tables')->where(function ($query) use ($request) {
                    return $query->where(['number' => $request->number, 'branch_id' => $request->branch_id]);
                }),
            ],
            'branch_id' => 'required',
            'capacity' => 'required|min:1|max:99',
        ], [
            'number.required' => translate('Table number is required!'),
            'number.unique' => translate('Table number is already exist in this branch!'),
            'capacity.required' => translate('Table capacity is required!'),
            'branch_id.required' => translate('Branch select is required!'),
        ]);



        $table = new Table();
        $table->number = $request->number;
        $table->capacity = $request->capacity;
        $table->branch_id = $request->branch_id;
        $table->is_active = 1;
        $table->save();

        Toastr::success(translate('Table added successfully!'));
        return redirect()->route('admin.table.list');
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
        $branches = Branch::orderBy('id', 'DESC')->get();
        $table = Table::where(['id' => $id])->first();
        return view('admin-views.table.edit', compact('table', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'number'  => [
                'required',
                Rule::unique('tables')->where(function ($query) use ($request, $id) {
                    return $query->where(['number' => $request->number, 'branch_id' => $request->branch_id])
                        ->whereNotIn('id', [$id]);
                }),
            ],
            'branch_id' => 'required',
            'capacity' => 'required|min:1|max:99',
        ], [
            'number.required' => translate('Table number is required!'),
            'number.unique' => translate('Table number is already exist in this branch!'),
            'capacity.required' => translate('Table capacity is required!'),
            'branch_id.required' => translate('Branch select is required!'),
        ]);

        $table = Table::find($id);
        $table->number = $request->number;
        $table->capacity = $request->capacity;
        $table->branch_id = $request->branch_id;
        $table->update();

        Toastr::success(translate('Table updated successfully!'));
        return redirect()->route('admin.table.list');
    }

    public function delete(Request $request)
    {
        $table = Table::find($request->id);
        $table->delete();
        Toastr::success(translate('Table removed!'));
        return back();
    }

    public function index()
    {
        $branches = Branch::orderBy('id', 'DESC')->get();
        return view('admin-views.table.index2', compact('branches'));
    }

    public function getTableListByBranch(Request $request)
    {
        $tables = Table::with(['order'=> function ($q){
            $q->whereHas('table_order', function($q){
                $q->where('branch_table_token_is_expired', 0);
            });
        }])->where(['branch_id' => $request->branch_id, 'is_active' => '1'])->get()->toArray();

        $view = view('admin-views.table.table_available_card2', compact('tables'))->render();

        return response()->json([
            'view' => $view,
        ]);
    }

}
