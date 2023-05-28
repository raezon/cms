<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\AdminRole;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class CustomRoleController extends Controller
{
    public function create(Request $request)
    {
        $search = $request['search'];
        $rl=AdminRole::whereNotIn('id',[1])
            ->when($search, function ($query) use($search) {
                $params = explode(' ', $search);
                foreach ($params as $param) {
                    $query->where('name', 'like', "%".$param."%");
                }
            })
            ->latest()->get();

        return view('admin-views.custom-role.create',compact('rl'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:admin_roles',
        ],[
            'name.required'=>translate('Role name is required!')
        ]);

        if($request['modules'] == null) {
            Toastr::error(translate('Select at least one module permission'));
            return back();
        }

        DB::table('admin_roles')->insert([
            'name'=>$request->name,
            'module_access'=>json_encode($request['modules']),
            'status'=>1,
            'created_at'=>now(),
            'updated_at'=>now()
        ]);

        Toastr::success(translate('Role added successfully!'));
        return back();
    }

    public function edit($id)
    {
        $role=AdminRole::where(['id'=>$id])->first(['id','name','module_access']);
        return view('admin-views.custom-role.edit',compact('role'));
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'name' => 'required',
        ],[
            'name.required'=> translate('Role name is required!')
        ]);

        DB::table('admin_roles')->where(['id'=>$id])->update([
            'name'=>$request->name,
            'module_access'=>json_encode($request['modules']),
            'status'=>1,
            'updated_at'=>now()
        ]);

        Toastr::success(translate('Role updated successfully!'));
        return redirect(route('admin.custom-role.create'));
    }

    public function delete(Request $request)
    {
        $role_exist = Admin::where('admin_role_id', $request->id)->first();
        if ($role_exist) {
            Toastr::warning(translate('employee_assigned_on_this_role._Delete_failed'));
        }
        else {
            $action = AdminRole::destroy($request->id);
            if ($action) {
                Toastr::success(translate('role_deleted_sucessfully'));
            }
            else {
                Toastr::warning(translate('delete_failed'));
            }
        }
        return back();
    }

    public function excel_export()
    {
        $roles = AdminRole::select('id', 'name', 'module_access', 'status')->get();
        return (new FastExcel($roles))->download('employee_role.xlsx');
    }

    public function status_change($id, Request $request)
    {
//        return response()->json($id);
        $role_exist = Admin::where('admin_role_id', $id)->first();
        if ($role_exist) {
            return response()->json(translate('employee_assigned_on_this_role._Update_failed'), 409);
        }
        else {
            $action = AdminRole::where('id', $id)->update(['status' => $request['status']]);
            if ($action) {
                return response()->json(translate('status_changed_successfully'), 200);
            }
            else {
                return response()->json(translate('status_update_failed'), 500);
            }
        }
    }
}
