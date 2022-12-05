<?php

namespace App\Http\Controllers;

use App\Models\CourseStudent;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Carbon\Carbon;

class PaymentController extends Controller
{
    use ApiResponser;
    use SlugTrait;
    use TranslationTrait;

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DataTables::of(PaymentTransaction::query()->where('payment_status_id', 2)->orderBy('created_at', 'desc'))
        ->addColumn('approve', function ($input) {
            $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus" checked >';
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('course', function ($payment) {
            return ($payment->courseStudent ? $payment->courseStudent->course->name_ar : ' ');
        })
        ->addColumn('student', function ($payment) {
            return ($payment->courseStudent ? $payment->courseStudent->student->first_name : ' ');
        })
        ->addColumn('id', function ($payment) {
            return ($payment->courseStudent ? $payment->courseStudent->student->id : ' ');
        })
        ->addColumn('created_at', function ($payment) {
            return $payment->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'approve', 'course', 'student', 'id'])
        ->make(true);
    }
    public function notPaidPayment()
    {
        return DataTables::of(PaymentTransaction::query()->where('payment_status_id', 1)->orderBy('created_at', 'desc'))
        ->addColumn('approve', function ($input) {
            $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus">';
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('course', function ($payment) {
            return ($payment->courseStudent ? $payment->courseStudent->course->name_ar : ' ');
        })
        ->addColumn('student', function ($payment) {
            return ($payment->courseStudent ? $payment->courseStudent->student->first_name : ' ');
        })
        ->addColumn('created_at', function ($payment) {
            return $payment->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'approve', 'course', 'student'])
        ->make(true);
    }
    public function show($id)
    {
        $payment = PaymentTransaction::with([
            'courseStudent',
            'courseStudent.course',
            'courseStudent.student'])->find($id);
        $payment->courseStudent->course->name = $payment->courseStudent->course->name_ar;
        $registered_number = PaymentTransaction::count();
        return $this->success(['payment' => $payment , 'registered_number' => $registered_number]);
    }
    public function list($id)
    {
        $payment = CourseStudent::with([
            'studentPayments',
            'course',
            'student'])->where('student_id', $id)->find($id);
        return $this->success(['payment' => $payment]);
    }
    public function approve($id)
    {
        $payment = PaymentTransaction::find($id);
        if ($payment) {
            $payment->payment_status_id = ($payment->payment_status_id == 1 ? 2 : 1);
            if ($payment->payment_status_id == 2) {
                $date_of_payment = Carbon::now()->format('Y-m-d');
                $payment->updated_at = $date_of_payment;
                $payment->save();
                return $this->success($payment, __('main.payment_paid'));
            } else {
                $payment->save();
                return $this->success($payment, __('main.payment_not_paid'));
            }
        }
        return $this->error(__('main.item_not_found'), 404);
    }
    public function destroy($id)
    {
        $payment = PaymentTransaction::find($id);
        $registered = CourseStudent::where('id', $payment->registered_course_id)->first();
        if ($registered) {
            $registered->delete();
            $payment->delete();
            return $this->success('', trans('main.payment_delete_success'));
        } else {
            $payment->delete();
            return $this->success('', trans('main.payment_delete_success'));
        }
    }
}
