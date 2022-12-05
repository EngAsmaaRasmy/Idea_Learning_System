<?php

use App\Http\Controllers\SmsController;
use App\Models\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/migrate', function () {
    // $course = DB::update('update slugs set model = "Course" where model = "Topic" ');
    // $course = DB::update('update students set phone_key = "00249"');
    // $course = DB::insert('insert into payment_transactions (registered_course_id, payment_status_id, cost, payment_method_id, created_at, updated_at)
    //                     select  course_students.id, course_students.status_id , courses.cost, 1, course_students.created_at, course_students.updated_at
    //                     FROM course_students
    //                     INNER JOIN courses ON course_students.course_id=courses.id;');
    //  $course = DB::update('update students set mobile = concat("+", substring(mobile,3)) where mobile LIKE "00%"');
    // return($course);
});
Route::get('/migrate-fresh', function () {
    Artisan::call('migrate', array('--force' => true));
    Artisan::call('route:clear');
    // Artisan::call('route:cache');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return "migrate";
});
Route::get('/test', function () {
    dump('done2');
});
