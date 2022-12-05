<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class MsAuthController extends Controller
{

    public function login(Request $request)
    {
        $input = $request->all();
        $client = new Client();
        $request = $client->post(env('MS_URL') . 'user/login', $params = [
            'query' => [
                'email' => $input['email'],
                'password' => $input['password'],
                'x-api-key' => env('MS_TOKEN')
            ]
        ]);

        return response()->json(json_decode($request->getBody()));
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
              'data' => [],
              'error' => true,
              'message' => __('main.token_is_empty')
            ]);
        }
        $client = new Client();
        $request = $client->post(env('MS_URL') . 'logout', $params = [
            'query' => ['token' => $token],
        ]);
        return response()->json(json_decode($request->getBody()));
    }
}
