<?php
namespace App\Http\Controllers\Api\V1\Auth;
ini_set('memory_limit', '-1');
use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Model\BusinessSetting;
use App\Model\EmailVerifications;
use App\Model\PhoneVerification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;

class CustomerAuthController extends Controller
{
   

  
    public function check_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:11|max:14|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator),'status'=>'Forbidden'], 403);
        }

        if (BusinessSetting::where(['key' => 'phone_verification'])->first()->value) {
            $token = rand(1000, 9999);
            DB::table('phone_verifications')->insert([
                'phone' => $request['phone'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $response = SMS_module::send($request['phone'], $token);
            return response()->json([
                'message' => $response,
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => translate('Number is ready to register'),
                'token' => 'inactive'
            ], 200);
        }
    }

    public function check_email(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|unique:users']);
        if ($validator->fails()) {  return response()->json(['errors' => Helpers::error_processor($validator),'status'=>'Forbidden'], 403); }
        if (BusinessSetting::where(['key' => 'email_verification'])->first()->value) {
            $token = rand(1000, 9999);
            DB::table('email_verifications')->insert(['email' => $request['email'], 'token' => $token,'created_at' => now(),'updated_at' => now(),]);
            try {
                $emailServices = Helpers::get_business_settings('mail_config');
                if (isset($emailServices['status']) && $emailServices['status'] == 1) {  Mail::to($request['email'])->send(new EmailVerification($token));  }} catch (\Exception $exception) {
                return response()->json([ 'message' => translate('Token sent failed'),'status'=>'Forbidden'], 403);}
            return response()->json([ 'message' => 'Email is ready to use', 'token' => 'active','status'=>'ok'], 200); } else {
            return response()->json(['message' => 'Email is ready to use', 'token' => 'inactive','status'=>'ok'  ], 200);}
    }
    public function verify_email(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required']);
        if ($validator->fails()) { return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $verify = EmailVerifications::where(['email' => $request['email'], 'token' => $request['token']])->first();
        if (isset($verify)) {
            $verify->delete();
            return response()->json([
                'message' => translate('OTP verified!'),
            ], 200);
        }
        return response()->json(['errors' => [
            ['code' => 'otp', 'message' => translate('OTP is not found!')]
        ]], 404);
    }

    public function verify_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $verify = PhoneVerification::where(['phone' => $request['phone'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            $verify->delete();
            return response()->json([
                'message' => translate('OTP verified!'),
            ], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'token', 'message' => translate('OTP is not found!')]
        ]], 404);
    }
   public function verify_email_phone(Request $request)
    {
        $validator = Validator::make(
        $request->all(), [
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users|min:5|max:20',
        ], [
            'f_name.required' => translate('The first name field is required.'),
            'l_name.required' => translate('The last name field is required.'),
        ] );
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator),'status'=>'Forbidden'], 403);
        }
        return response()->json(['message' =>'phone and email not exist','status'=>'ok'], 200);
    }
    public function registration(Request $request)
    {
      
             $user = new User() ;
            $user->f_name = $request['f_name'];
            $user->l_name = $request['l_name'];
             $user->email= $request['email'];
             $user->phone = $request['phone'];
             $user->is_phone_verified= "1";
             $user->password = bcrypt($request['password']);
             $user->temporary_token =$request['token'];
             $user->cm_firebase_token=$request['token'];
             $user->save();
        
         if (BusinessSetting::where(['key' => 'phone_verification'])->first()->value) {
          $verify = PhoneVerification::where(['phone' => $request['phone'], 'token' => $request['otp_code']])->first();
            if (isset($verify)) {}
        else {
             $verify = PhoneVerification::where(['phone' => $request['phone']])->first();
            if (isset($verify)) {
             $verify->delete();
             DB::table('phone_verifications')->insert([ 'phone' => $request['phone'],'token' => $request['otp_code'], 'created_at' => now(),'updated_at' => now(), ]);
             } 
            else {
              DB::table('phone_verifications')->insert([ 'phone' => $request['phone'],'token' => $request['otp_code'], 'created_at' => now(),'updated_at' => now(), ]);
             }
        }
        }
        if (BusinessSetting::where(['key' => 'email_verification'])->first()->value) {
            DB::table('email_verifications')->insert(['email' => $request['email'], 'token' => $request['otp_code'],'created_at' => now(),'updated_at' => now(),]);
            }
      $token=$request->token;
        return response()->json(['token' => $token,'status'=>'ok'], 200);
    }
    public function login(Request $request)
    {
        if($request->has('email_or_phone')) {
            $user_id = $request['email_or_phone'];
            $validator = Validator::make($request->all(), [
                'email_or_phone' => 'required',
                'password' => 'required|min:6'
            ]);
        }else{
            $user_id = $request['email'];
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required|min:6'
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator),'status'=>'Forbidden'], 403);
        }

        $user = User::where('is_active', 1)
            ->where(function ($query) use($user_id) {
                $query->where(['email' => $user_id])->orWhere('phone', $user_id);
            })
            ->first();

        if (isset($user)) {
            $user->temporary_token = $request->token;
            $user->cm_firebase_token = $request->token;
            $user->is_phone_verified= "1";
            $user->save();
            $data = [
                'email' => $user->email,
                'password' => $request->password,
                'user_type' => null,
            ];
               $token=$request->token;
            if (auth()->attempt($data)) {
              
                return response()->json(['token' => $token,'data'=>$user,'status'=>'ok','type_token'=>'bearer'], 200);
            }
        }

        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Invalid credential.'];
        return response()->json([
            'errors' => $errors,'status'=>'Unauthorized'
        ], 401);

    }

    public function remove_account(Request $request)
    {
       
        $customer = User::where('id',$request['user_id'])->first();

        if(isset($customer)) {
            Helpers::file_remover('customer/', $customer->image);
            $customer->delete();
        } else {
            return response()->json(['message' =>'Not found','status'=>'not found'], 404);
        }
        return response()->json(['status'=>'ok', 'message' =>'Successfully deleted'], 200);
    }

    public function social_customer_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }
        } catch (\Exception $exception) {
            $errors = [];
            $errors[] = ['code' => 'auth-001', 'message' => 'Invalid Token'];
            return response()->json([
                'errors' => $errors
            ], 401);
        }

        if (strcmp($email, $data['email']) === 0) {
            $user = User::where('email', $request['email'])->first();

            if (!isset($user)) {
                $name = explode(' ', $data['name']);
                if (count($name) > 1) {
                    $fast_name = implode(" ", array_slice($name, 0, -1));
                    $last_name = end($name);
                } else {
                    $fast_name = implode(" ", $name);
                    $last_name = '';
                }

                $user = new User();
                $user->f_name = $fast_name;
                $user->l_name = $last_name;
                $user->email = $data['email'];
                $user->phone = null;
                $user->image = 'def.png';
                $user->password = bcrypt(rand(100000, 999999));
                $user->login_medium = $request['medium'];
                $user->save();
            }

            if (isset($user)){
                if ($user->is_active == 1){
                    $token = $user->createToken('AuthToken')->accessToken;
                    return response()->json([
                        'errors' => null,
                        'token' => $token,
                    ], 200);
                }else{
                    $errors = [];
                    $errors[] = ['code' => 'auth-001', 'message' => 'Unauthenticated.'];
                    return response()->json([
                        'errors' => $errors
                    ], 401);
                }
            }
        }

        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Invalid Token'];
        return response()->json([
            'errors' => $errors
        ], 401);
    }

}
