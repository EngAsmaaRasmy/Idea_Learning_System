<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class SmsController extends Controller
{
    //
    private static $sender = "School";
    private static $user = "ma.aziz";
    private static $password = "ma@123";


    public static function sendMassage($phone, $message)
    {
        $isError = 0;
        $errorMessage = true;
        $url = "http://196.202.134.90/dsms/webacc.aspx?";
        $url .= "user=" . self::$user;
        $url .= "&pwd=" . self::$password;
        $url .= "&smstext=" . $message;
        $url .= "&Sender=" . self::$sender;
        $url .= "&Nums=" . $phone;
        $response = Http::get($url);
        return $response;
    }
}
