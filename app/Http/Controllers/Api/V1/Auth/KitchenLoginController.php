<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class KitchenLoginController extends Controller
{
    public function login(Request $request)
    {
        //dd($request);
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (is_numeric($request['email_or_phone'])){
            $data = [
                'phone' => $request['email_or_phone'],
                'password' => $request->password,
                'is_active' => 1,
                'user_type' => 'kitchen',
            ];
        }
        elseif (filter_var($request['email_or_phone'], FILTER_VALIDATE_EMAIL)){
            $data = [
                'email' => $request['email_or_phone'],
                'password' => $request->password,
                'is_active' => 1,
                'user_type' => 'kitchen',
            ];
        }
        else{
            $data =[];
        }

        //dd($data);

        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('KitchenChefAuth')->accessToken;
            return response()->json([
                'user' => auth()->user(),
                'token' => $token,
                'message' => 'Successfully login.'
            ], 200);
        }

        $errors = [];
        array_push($errors, ['code' => 'auth-001', 'message' => 'Invalid credential.']);
        return response()->json([
            'errors' => $errors
        ], 401);

    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }

}
