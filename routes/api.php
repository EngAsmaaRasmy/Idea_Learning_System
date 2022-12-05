<?php

use App\Http\Controllers\SectionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CertificateRequestController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\MsAuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SiteProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UploaderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['prefix' => 'admin-dashboard'], function () {
    app()->setLocale('ar');
    Route::post('login', [MsAuthController::class, 'login']);
    Route::post('logout', [MsAuthController::class, 'logout']);
    Route::group(
        ['middleware' => ['admin_api_auth']],
        function () {
            Route::resource('courses', CourseController::class);
            Route::get('filter-students/{id}', [CourseController::class, 'filter']);
            Route::resource('payment', PaymentController::class);
            Route::get('not-paid-payment', [PaymentController::class, 'notPaidPayment']);
            Route::resource('certificate', CertificateRequestController::class);
            Route::get('certificate/{id}/approve', [CertificateRequestController::class, 'approve']);
            Route::resource('categories', CategoryController::class);
            Route::get('categories/{id}/sub-categories', [CategoryController::class, 'subCategories']);
            Route::resource('sub-categories', SubCategoryController::class);
            Route::get('sub-categories/{id}/courses', [SubCategoryController::class, 'courses']);
            Route::resource('topics', TopicController::class);
            Route::get('topics/{id}/courses', [TopicController::class, 'courses']);
            Route::resource('instructors', InstructorController::class);
            Route::resource('sections', SectionController::class);
            Route::get('courses/{id}/sections', [CourseController::class, 'courses']);
            Route::resource('lessons', LessonController::class);
            Route::get('free/{id}', [LessonController::class, 'free']);
            Route::resource('students', StudentController::class);
            Route::resource('courses-students', CourseStudentController::class);
            Route::get('students/{id}/block', [StudentController::class, 'blocked']);
            Route::get('courses-students/{id}/approve', [CourseStudentController::class, 'approve']);
            Route::get('courses/{id}/hide', [CourseController::class, 'hide']);
            Route::get('payment/{id}/approve', [PaymentController::class, 'approve']);
            Route::get('site-profile', [SiteProfileController::class, 'show']);
            Route::post('site-profile/update', [SiteProfileController::class, 'update']);
            Route::get('statistics', [HomeController::class, 'statistics']);
            Route::post('medialibrary/upload', [UploaderController::class, 'upload'])->name('file-upload');
            Route::post('medialibrary/delete', [UploaderController::class, 'delete'])->name('file-delete');
        }
    );
});
Route::group(['prefix' => 'website'], function () {
    Route::get('categories', [CategoryController::class, 'list']);
    Route::get('categories/{slug}/courses', [CategoryController::class, 'courses']);
    Route::get('sub-categories/{slug}/courses', [SubCategoryController::class, 'courses']);
    Route::get('topics/{slug}/courses', [TopicController::class, 'courses']);
    Route::get('site-profile', [SiteProfileController::class, 'show']);
    Route::get('courses/new', [CourseController::class, 'list']);
    Route::get('courses', [HomeController::class, 'courses']);
    Route::get('instructors', [HomeController::class, 'instructors']);
    Route::get('instructor/{id}', [HomeController::class, 'instructor']);
    Route::get('courses/{slug}', [HomeController::class, 'course']);
    Route::post('courses/search', [CourseController::class, 'search']);
    Route::get('lessons/{id}', [HomeController::class, 'lesson']);
    Route::get('home', [HomeController::class, 'index']);
    Route::post('students/login', [StudentController::class, 'login']);
    Route::post('students/register', [StudentController::class, 'store']);
    Route::get('students/confirmation-code/{email}', [StudentController::class, 'resendConfirmationCode']);
    Route::get('students/confirmation-code-mobile/{mobile}', [StudentController::class, 'resendConfirmationCodeForMobile']);
    Route::post('students/confirmation-code', [StudentController::class, 'confirmationCode']);
    Route::get('countries', [HomeController::class, 'countries']);
    Route::group(
        ['middleware' => ['student_api_auth']],
        function () {
            Route::get('profile', [StudentController::class, 'show']);
            Route::post('data-for-certificate', [StudentController::class, 'certificate']);
            // Route::get('certificate', [CertificateRequestController::class, 'show']);
            Route::post('update-profile', [StudentController::class, 'update']);
            Route::post('course-registeration', [StudentController::class, 'courseRegister']);
            Route::get('student-registered/{slug}', [StudentController::class, 'checkRegistration']);
            Route::post('student-progress/{lesson_id}', [StudentController::class, 'progress']);
            Route::get('logout', [StudentController::class, 'logout']);
        }
    );
});
