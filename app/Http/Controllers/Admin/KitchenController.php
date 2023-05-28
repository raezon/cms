<?php

namespace App\Http\Controllers\Admin;

use App\Model\Branch;
use App\User;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    public function add_new()
    {
        $branches = Branch::orderBy('id', 'DESC')->get();
        return view('admin-views.kitchen.add-new', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'phone'=>   'required|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password'=>'required|min:6',
            'image' => 'required',
            'branch_id' => 'required',
        ], [
            'f_name.required' => translate('First name is required!'),
            'l_name.required' => translate('Last name is required!'),
            'phone.required' => translate('Phone is required'),
            'phone.unique' => translate('This phone is already taken! please try another one'),
            'email.required' => translate('Email is Required'),
            'email.email' => translate('Field type must be email'),
            'email.unique' => translate('This email is already taken! please try another one'),
            'password.required' => translate('Password is Required'),
            'password.min' => translate('Password length must be 6 character'),
            'image.required' => translate('Image is Required'),
            'branch_id.required' => translate('Branch select is required'),
        ]);

        DB::beginTransaction();
        try {
            $chef = new User();
            $chef->f_name = $request->f_name;
            $chef->l_name = $request->l_name;
            $chef->phone = $request->phone;
            $chef->email = $request->email;
            $chef->user_type = 'kitchen';
            $chef->is_active = 1;
            $chef->password = bcrypt($request->password);
            $chef->image = Helpers::upload('kitchen/', 'png', $request->file('image'));
            $chef->save();

            $chef_id = $chef->id;

            $data = [
                'user_id' => $chef_id,
                'branch_id' => $request->branch_id,
            ];
            DB::table('chef_branch')->insert($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }

        Toastr::success(translate('Chef added successfully!'));
        return redirect()->route('admin.kitchen.list');
    }

    function list(Request $request)
    {
        $search = $request['search'];
        $key = explode(' ', $request['search']);
        $chefs = User::where('user_type', 'kitchen')
            ->when($search!=null, function($query) use($key){
                $query->where( function ($q) use($key){
                    foreach ($key as $value) {
                        $q->where('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                }});

            })
            ->orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination());

//        return
        return view('admin-views.kitchen.list', compact('chefs','search'));
    }

    public function status(Request $request)
    {
        $kitchen = User::find($request->id);
        $kitchen->is_active = $request->status;
        $kitchen->save();

        Toastr::success(translate('Chef status updated!'));
        return back();
    }

    public function edit(Request $request)
    {
        $branches = Branch::orderBy('id', 'DESC')->get();
        $chef = User::find($request->id);
        $chef_branch = DB::table('chef_branch')->where('user_id', $chef->id)->first();
        return view('admin-views.kitchen.edit', compact('chef', 'branches', 'chef_branch'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'phone'=> 'required|unique:users,phone,'.$id,
            'email' => 'required|email|unique:users,email,'.$id,
            'branch_id' => 'required',
        ], [
            'f_name.required' => translate('First name is required!'),
            'l_name.required' => translate('Last name is required!'),
            'phone.required' => translate('Phone is Required'),
            'phone.unique' => translate('This email is already taken! please try another one'),
            'email.required' => translate('Email is Required'),
            'email.email' => translate('Field type must be email'),
            'email.unique' => translate('This email is already taken! please try another one'),
            'branch_id.required' => translate('Branch select is required'),
        ]);

        DB::beginTransaction();
        try {
            $chef = User::find($request->id);

            if ($request['password'] == null) {
                $password = $chef['password'];
            } else {
                if (strlen($request['password']) < 5) {
                    Toastr::warning(translate('Password length must be 6 character.'));
                    return back();
                }
                $password = bcrypt($request['password']);
            }

            $chef->f_name = $request->f_name;
            $chef->l_name = $request->l_name;
            $chef->phone = $request->phone;
            $chef->email = $request->email;
            $chef->password = $password;
            $chef->image = $request->has('image') ? Helpers::update('kitchen/', $chef->image, 'png', $request->file('image')) : $chef->image;
            $chef->update();

            $chef_id = $chef->id;

            DB::table('chef_branch')->where('user_id', $chef_id)->update([
                'user_id' => $chef_id,
                'branch_id' => $request->branch_id,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
        Toastr::success(translate('Chef updated successfully!'));
        return back();
    }

    public function delete(Request $request)
    {
        $chef = User::find($request->id);
        Helpers::delete('kitchen/' . $chef['image']);
        $chef->delete();
        DB::table('chef_branch')->where('user_id', $chef->id)->delete();
        Toastr::success(translate('Chef removed!'));
        return back();
    }
}
