<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\BusinessSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SystemController extends Controller
{
    public function restaurant_data()
    {
        $new_order = DB::table('orders')->where(['checked' => 0])->count();
        return response()->json([
            'success' => 1,
            'data' => ['new_order' => $new_order]
        ]);
    }

    public function settings()
    {
        return view('admin-views.settings');
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'email' => ['required', 'unique:admins,email,' . auth('admin')->id() . ',id'],
            'phone' => 'required',
        ], [
            'f_name.required' => translate('First name is required!'),
        ]);

        $admin = Admin::find(auth('admin')->id());
        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->image = $request->has('image') ? Helpers::update('admin/', $admin->image, 'png', $request->file('image')) : $admin->image;
        $admin->save();
        Toastr::success(translate('Admin updated successfully!'));
        return back();
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ]);
        $admin = Admin::find(auth('admin')->id());
        $admin->password = bcrypt($request['password']);
        $admin->save();
        Toastr::success(translate('Admin password updated successfully!'));
        return back();
    }

    public function app_activate(Request $request, $app_id)
    {
        $app_name = 'default';
        $app_link = 'default';
        foreach (APPS as $app) {
            if ($app['software_id'] == $app_id) {
                $app_name = $app['app_name'];
                $app_link = $app['buy_now_link'];
            }
        }

        return view('admin-views.app-activation', compact('app_id', 'app_name','app_link'));
    }

    public function activation_submit(Request $request, $app_id): \Illuminate\Http\RedirectResponse
    {
        $post = [
            'purchase_key' => $request['purchase_key']
        ];
        $live = 'https://check.6amtech.com';
        $ch = curl_init($live . '/api/v1/software-check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_body = json_decode($response, true);

        try {
            if ($response_body['is_valid'] && $response_body['result']['item']['id'] == $app_id) {
                $previous_active = json_decode(BusinessSetting::where('key', 'app_activation')->first()->value ?? '[]');
                $found = 0;
                foreach ($previous_active as $key => $item) {
                    if ($item->software_id == $app_id) {
                        $found = 1;
                    }
                }
                if (!$found) {
                    $previous_active[] = [
                        'software_id' => $app_id,
                        'is_active' => 1
                    ];
                    DB::table('business_settings')->updateOrInsert(['key' => 'app_activation'], [
                        'value' => json_encode($previous_active)
                    ]);
                }

                Toastr::success('succesfully activated');
                return back();
            }

        } catch (\Exception $exception) {
            Toastr::warning('invalid purchase code');
            return back();
        }

        Toastr::warning('invalid purchase code');
        return back();
    }
}
