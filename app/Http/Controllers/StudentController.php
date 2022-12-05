<?php

namespace App\Http\Controllers;

use App\Models\CourseStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use DataTables;
use App\Traits\SlugTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Log;
use App\Models\PaymentTransaction;
use App\Models\StudentProgress;
use Carbon\Carbon;

class StudentController extends Controller
{
    use ApiResponser;
    use SlugTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DataTables::of(Student::query()->whereNotNull('otp')->orderBy('created_at', 'desc'))
        ->addColumn('block', function ($student) {
            if ($student->blocked == 0) {
                $input = '<input data-action="block" type="checkbox" class="switch" name="switchstatus" >';
            } else {
                $input = '<input data-action="block" type="checkbox" class="switch" name="switchstatus" checked>';
            }
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('created_at', function ($student) {
            return $student->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'block'])
        ->make(true);
    }

    public function list()
    {
        $students = Student::orderby('created_at', 'DESC')->get();

        return $this->success($students);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function certificate(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name_one' => 'required|string',
            'name_two' => 'required|string',
            'name_three' => 'required|string',
            'name_four' => 'required|string',
            'name_one_ar' => 'required',
            'name_two_ar' => 'required',
            'name_three_ar' => 'required',
            'name_four_ar' => 'required',
            'image' =>  'nullable|mimes:jpg,png,jpeg,gif,svg',
            'student_id' => 'required',
            'course_id' => 'required',
        ]);
        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $id = $request->input('student_id');
        $courseId = $request->input('course_id');
        $student = Student::find($id);
        $full_name = [$request->input('name_one'),
        $request->input('name_two'),
        $request->input('name_three'),
         $request->input('name_four')];
        $full_name_ar = [$request->input('name_one_ar'),
         $request->input('name_two_ar'),
        $request->input('name_three_ar'),
         $request->input('name_four_ar')];
        $student->full_name = json_encode($full_name);
        $student->full_name_ar = json_encode($full_name_ar);
        $student->update([
            'full_name' => $student->full_name,
            'full_name_ar' => $student->full_name_ar,
        ]);
        $certificate = CertificateRequest::where('student_id', $id)->where('course_id', $courseId)->first();
        if (!$certificate) {
            $certificate = CertificateRequest::create([
                'student_id' => $id ,
                'course_id' => $courseId,
                'status_id' => 1,
            ]);
        } elseif ($certificate) {
            return $this->error(__('main.wrong'), 401);
        }
        $uploads_folder =  getcwd() . '/uploads/students/';
        if (!file_exists($uploads_folder)) {
             mkdir($uploads_folder, 0777, true);
        }
        if ($request->file('image')) {
            $image_name = md5($student->id . "app" . $student->id . rand(1, 1000));
            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc
            $image_full_name = $image_name . '.' . $image_ext;
            $uploads_folder =  getcwd() . '/uploads/students/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);
            $student->image =  $image_full_name;
        }
        $student->save();

        return $this->success(['student' => $student], trans('main.student_data_certificate_create_success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            app()->setLocale('ar');
        } else {
            app()->setLocale('en');
        }
        $input = $request->all();
        $local = $request->header('Accept-Language');
        $validator = Validator::make($input, [
            'first_name' => 'required|string',
            'email' => 'required|email|unique:students,email',
            'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|unique:students,mobile',
            'key' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
            'last_name' => 'required|string',
            'password' => 'required|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        if ($input['key'] == "00249") {
            $input['phone'] = $input['key'] . '' . $input['mobile'];
            $validator = Validator::make($input, [
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|unique:students,mobile',
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
        }
        $student = Student::create($input);
        $full_name = [$request->input('first_name'),
        $request->input('last_name')];
        $full_name_ar = [$request->input('first_name'),
        $request->input('last_name')];
        $student->full_name = json_encode($full_name);
        $student->full_name_ar = json_encode($full_name_ar);
        $student->password = Hash::make($input['password']);
        $student->phone_key = $input['key'];
        $student->mobile = $input['key'] . '' . $input['mobile'];
        $input['otp'] = rand(10000, 99999);
        $message = trans('main.confirmation_message') . "" . $input['otp'];
        if ($input['key'] == "00249") {
            $mobile = substr($student->mobile, 2);
            $response = SmsController::sendMassage($mobile, $message);
            $log = Log::create([
                    'response' => $response,
                    'mobile' => $mobile,
            ]);
        } else {
            Mail::to($student->email)->send(new SendMail($input));
        }
        $student->otp = $input['otp'];
        $student->save();
        return $this->success(['student' => $student], trans('main.student_create_success'));
    }

    public function createOtp($mobile)
    {
        $otp_code = rand(10000, 99999);
        return $otp_code;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id = null)
    {
        $local = $request->header('Accept-Language');
        if ($id) {
            $student = Student::with(['courses' , 'certificate' , 'courseStudent.studentPayments'])->find($id);
            if ($student->blocked == 1) {
                if ($request->bearerToken()) {
                    $token = $request->bearerToken();
                    $student = Student::where('token', '=', $token)->first();
                    if (!$student) {
                        return $this->error(__('main.account_is_not_found'), 404);
                    }
                    $student->token = null;
                    $student->save();
                    return $this->success([], __('main.logout_success'));
                }
                return $this->error(__('main.not_found'), 401);
            }
        } else {
            if ($request->bearerToken()) {
                $token = $request->bearerToken();
                $student = Student::with(['courses', 'courses.course' ,'certificate.course', 'payments.courseStudent.course'])->where('token', '=', $token)->first();
                $input['name_one'] = json_decode($student->full_name)[0] ?? '';
                $input['name_two'] = json_decode($student->full_name)[1] ?? '';
                $input['name_three'] = json_decode($student->full_name)[2] ?? '';
                $input['name_four'] = json_decode($student->full_name)[3] ?? '';
                $input['name_one_ar'] = json_decode($student->full_name_ar)[0] ?? '';
                $input['name_two_ar'] = json_decode($student->full_name_ar)[1] ?? '';
                $input['name_three_ar'] = json_decode($student->full_name_ar)[2] ?? '';
                $input['name_four_ar'] = json_decode($student->full_name_ar)[3] ?? '';
                $key = $student->phone_key;
                $mobile = $student->mobile;
                $input['phone'] = explode($key, $mobile)[0];
                if (explode($key, $mobile)[0] != "") {
                    $input['phone'] = explode($key, $mobile)[0];
                } else {
                    $input['phone'] = explode($key, $mobile)[1];
                }
                if ($local && $local == 'ar') {
                    foreach ($student->courses as $courses) {
                        $courses->course->name = $courses->course->name_ar ;
                    } foreach ($student->certificate as $certificate) {
                        $certificate->course->name = $certificate->course->name_ar ;
                    } foreach ($student->payments as $payments) {
                         $payments->courseStudent->course->name = $payments->courseStudent->course->name_ar ;
                    }
                    return $this->success(['student' => $student ,'input' => $input]);
                }
                if ($student->blocked == 1) {
                    if ($request->bearerToken()) {
                        $token = $request->bearerToken();
                        $student = Student::where('token', '=', $token)->first();
                        if (!$student) {
                            return $this->error(__('main.account_is_not_found'), 404);
                        }
                        $student->token = null;
                        $student->save();
                        return $this->success([], __('main.logout_success'));
                    }
                    return $this->error(__('main.not_found'), 401);
                }
            }
        }
        if (!$student) {
            return $this->error(__('main.account_is_not_found'), 404);
        }
        return $this->success(['student' => $student , 'input' => $input]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $student = Student::find($id);

        return $this->success(['student' => $student]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id = null)
    {
        $input = $request->all();
        if ($id) {
            $student = Student::find($id);
        } else {
            if ($request->bearerToken()) {
                $token = $request->bearerToken();
                $student = Student::where('token', '=', $token)->first();
            }
        }
        if (!$student) {
            return $this->error(__('main.account_is_not_found'), 404);
        }
        $validator = Validator::make($input, [
          'first_name' => 'required|string',
          'email' => 'required|email',
          'mobile' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/',
          'last_name' => 'required|string',
          'password' => 'nullable|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $student->update($input);
        if ($request->password != null) {
            $student->password = Hash::make($input['password']);
            $student->save();
        }
        $full_name = [$request->input('name_one'),
        $request->input('name_two'),
        $request->input('name_three'),
         $request->input('name_four')];
        $full_name_ar = [$request->input('name_one_ar'),
         $request->input('name_two_ar'),
         $request->input('name_three_ar'),
         $request->input('name_four_ar')];
        $student->full_name = json_encode($full_name);
        $student->full_name_ar = json_encode($full_name_ar);
        $student->mobile = $student->phone_key . '' . $input['phone'];
        if ($request->file('image')) {
            $image_name = md5($student->id . "app" . $student->id . rand(1, 1000));
            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc
            $image_full_name = $image_name . '.' . $image_ext;
            $uploads_folder =  getcwd() . '/uploads/students/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);
            $student->image =  $image_full_name;
        }
        $student->save();

        return $this->success(['student' => $student], __('main.student_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $student = Student::find($id);
        if (!$student) {
            return $this->error(__('main.account_is_not_found'), 404);
        }
        $courses = CourseStudent::where('student_id', $id)->get();
        if (count($courses) > 0) {
            return $this->error(trans('main.student_has_registered_course'), 422);
        }
        $student->delete();
        return $this->success('', trans('main.student_delete_success'));
    }

    public function register(Request $request)
    {
      # code...
    }

    public function login(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $student = Student::where('email', '=', $request->get('email'))->first();
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            app()->setLocale('ar');
        } else {
            app()->setLocale('en');
        }
        if ($student) {
            if (Hash::check($input['password'], $student->password)) {
                if ($student->verified == 0) {
                    return $this->error(__('main.account_is_not_verified'), 402, '');
                }
                if ($student->blocked == 1) {
                    return $this->error(__('main.account_is_blocked'), 402, '');
                }
                $token = uniqid(base64_encode(Str::random(40)));
                $student->token = $token;
                $student->save();
                return $this->success($student, trans('main.student_login_success'));
            }
            return $this->error(__('main.password_value_is_not_incorrect'), 402, $student);
        }
        return $this->error(__('main.account_is_not_found'), 402, $student);
    }

    public function confirmationCode(Request $request)
    {
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            app()->setLocale('ar');
        } else {
            app()->setLocale('en');
        }
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required_without:mobile',
            'mobile' => 'required_without:email',
            'code' => 'required|string',
        ]);
        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $student = Student::where('email', '=', $input['email'])
        ->orWhere('mobile', '=', $input['mobile'])->first();
        if ($student) {
            if ($input['code'] == $student->otp) {
                $token = uniqid(base64_encode(Str::random(40)));
                $student->verified = 1;
                $student->token = $token;
                $student->save();
                return $this->success($student, trans('main.student_confirmation_success'));
            }
            return $this->error(__('main.student_confirmation_error'), 402, '');
        }
        return $this->error(__('main.account_is_not_found'), 402, '');
    }

    public function courseRegister(Request $request)
    {
        $input = $request->all();
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $student = Student::where('token', '=', $token)->first();
            if (!$student) {
                return $this->error(__('main.not_found'), 404);
            }
            $validator = Validator::make($input, [
              'student_id' => 'required|integer',
              'course_id' => 'required|integer',
              'cost' => 'required',
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
            $courseStudent = CourseStudent::create($input);
            $course = Course::where('id', $courseStudent->course_id)->first();
            $payment = PaymentTransaction::create([
                'payment_method_id' => 1,
                'registered_course_id' => $courseStudent->id,
                'cost' => $course->cost,
            ]);
            if (!$payment) {
                return $this->error(__('main.wrong'), 401);
            }
            return $this->success($student, trans('main.student_course_registeration_success'));
        }
        return $this->error(__('main.account_is_not_found'), 401);
    }
    public function checkRegistration($slug, Request $request)
    {
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $student = Student::where('token', '=', $token)->first();
            if (!$student) {
                return $this->error(__('main.account_is_not_found'), 404);
            }
            $id = $this->getRowId('Course', $slug);
            if (!$id) {
                return $this->error(__('main.item_not_found'), 404);
            }
            $registered = CourseStudent::where('course_id', $id)->where('student_id', $student->id)->first();
            $certificate = CertificateRequest::where('course_id', $id)->where('student_id', $student->id)->first();
            if ($certificate) {
                $status = $certificate->status_id;
            } else {
                $status = 0;
            }
            if ($registered) {
                $payment = PaymentTransaction::where('registered_course_id', $registered->id)->first();
                if ($payment) {
                    if ($payment->payment_status_id == 2) {
                        $payment->date_of_payment = $payment->date_of_payment->format('Y-m-d');
                        $finalDate =  $payment->date_of_payment->addDays($registered->course->number_of_days)->format('Y-m-d');
                        if (Carbon::now() > $finalDate) {
                            return $this->error(__('main.time_end'), 402);
                        }
                    } elseif ($payment->payment_status_id == 1) {
                        $finalDate = $registered->course->number_of_days;
                    } else {
                        $finalDate = '';
                    }
                }
                if ($registered->status_id == 2 && $payment->payment_status_id == 2) {
                    $sections = $registered->course->sections->collect()->map(function ($section) {
                        return $section->id;
                    });
                    $lessons = Lesson::whereIn('section_id', $sections)->get()->pluck('id');
                    $registered->lessonsCount = count($lessons);
                    $progress = StudentProgress::with(['lesson', 'lesson.section'])
                    ->whereIn('lesson_id', $lessons)
                    ->where('student_id', $registered->student_id)
                    ->get();
                    $lessonTotalProgress = StudentProgress::with(['lesson', 'lesson.section'])
                    ->whereIn('lesson_id', $lessons)
                    ->where('student_id', $registered->student_id)
                    ->sum('progress');
                    $courseProgress = 0;
                    $lessonTotalProgress = $lessonTotalProgress / 100;
                    if (count($lessons) !== 0) {
                        $courseProgress = ($lessonTotalProgress / count($lessons)) * 100;
                    }
                    $registered->lessonTotalProgress = $lessonTotalProgress;
                    $registered->progress = $progress;
                    $registered->courseProgress = $courseProgress;
                    return $this->success(['registered' => $registered, 'certificate' => $status,  'finalDate' => $finalDate]);
                } elseif ($registered->status_id == 2 && $payment->payment_status_id == 1) {
                    return $this->error(__('main.not_approved_payment_in_course'), 402);
                } elseif ($registered->status_id == 1 && $payment->payment_status_id == 1) {
                    return $this->error(__('main.not_approved_register_in_course'), 402);
                }
            }
            return $this->error(__('main.not_registered_in_course'), 400);
        }
        return $this->error(__('main.account_is_not_found'), 401);
    }
    public function blocked(Request $request, $id)
    {
        $student = Student::find($id);
        if ($student) {
            $student->blocked = ($student->blocked == 0 ? 1 : 0);
            $student->save();
            if ($student->blocked == 1) {
                return $this->success($student, __('main.blocked_success'));
            } else {
                return $this->success($student, __('main.un_blocked_success'));
            }
        }
        return $this->error(__('main.item_not_found'), 404);
    }

    public function logout(Request $request)
    {
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $student = Student::where('token', '=', $token)->first();
            if (!$student) {
                return $this->error(__('main.account_is_not_found'), 404);
            }
            $student->token = null;
            $student->save();
            return $this->success([], __('main.logout_success'));
        }
        return $this->error(__('main.not_found'), 401);
    }

    public function progress(Request $request, $id)
    {
        $input = $request->all();
        if ($request->bearerToken()) {
            $token = $request->bearerToken();
            $student = Student::where('token', '=', $token)->first();
            if (!$student) {
                return $this->error(__('main.account_is_not_found'), 404);
            }
            $progress = StudentProgress::where('student_id', $student->id)->where('lesson_id', $id)->first();
            if (!$progress) {
                $progress = StudentProgress::create([
                  'student_id' => $student->id,
                  'lesson_id' => $id,
                  'progress' => $request->get('progress'),
                ]);
            } else {
                if ($progress->progress <= $request->get('progress')) {
                    $progress->progress = $request->get('progress');
                    $progress->save();
                }
            }
            return $this->success($progress, trans('main.success'));
        }
        return $this->error(__('main.account_is_not_found'), 401);
    }
    public function resendConfirmationCode(Request $request, $email)
    {
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            app()->setLocale('ar');
        } else {
            app()->setLocale('en');
        }
        $student = Student::where('email', $email)->first();
        if (!$student) {
            return $this->error(__('main.account_is_not_found'), 404);
        }
        $input['otp'] = rand(10000, 99999);
        Mail::to($student->email)->send(new SendMail($input));
        $student->otp = $input['otp'];
        $student->save();
        return $this->success(['student' => $student], trans('main.student_otp_sent'));
    }
    public function resendConfirmationCodeForMobile(Request $request, $mobile)
    {
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            app()->setLocale('ar');
        } else {
            app()->setLocale('en');
        }
        $student = Student::where('mobile', $mobile)->first();
        if (!$student) {
            return $this->error(__('main.account_is_not_found'), 404);
        }
        $input['otp'] = rand(10000, 99999);
        $message = trans('main.confirmation_message') . "" . $input['otp'];
        $response = SmsController::sendMassage($student->mobile, $message);
        $student->otp = $input['otp'];
        $student->save();
        return $this->success(['student' => $student], trans('main.student_otp_sent'));
    }
}
