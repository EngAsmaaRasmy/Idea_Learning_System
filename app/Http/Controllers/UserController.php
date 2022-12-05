<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\AdminLoginSession;
use App\Models\Configuration;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $input = $request->all();
        $user = User::where('email', $input['email'])->first();
        if ($user) {
            if (Hash::check($input['password'], $user->password)) {
                $token = uniqid(base64_encode(Str::random(40)));
                $session = AdminLoginSession::where('user_id')->first();
                if (!$session) {
                    $session = new AdminLoginSession();
                    $session->user_id = $user->id;
                }
                $session->token = $token;
                $session->save();
                $user->token = $token;
                $user->configuration = Configuration::first();
                return response()->json(['data' => $user,'error' => false,'message' => '']);
            } else {
                return response()->json(['data' => '','error' => true,'message' => trans('main.wrong_password')]);
            }
        } else {
            return response()->json(['data' => '','error' => true,'message' => trans('main.user_not_registered')]);
        }
    }


    public function logout(Request $request)
    {
        $input = $request->all();
        if (isset($input['token'])) {
            $session = AdminLoginSession::where('token', $input['token'])->first();
            if ($session) {
                $session->expired = 1;
                $session->save();
                return response()->json(['data' => '','error' => false,'message' => 'logout']);
            }
        }
        return response('Unauthorized.', 401);
    }
}
