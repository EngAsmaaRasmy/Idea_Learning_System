<?php

namespace App\Http\Controllers;

use App\Models\CourseStudent;
use App\Models\Lesson;
use App\Models\PaymentTransaction;
use App\Models\StudentProgress;
use App\Traits\ApiResponser;
use DataTables;
use Carbon\Carbon;

class CourseStudentController extends Controller
{
    use ApiResponser;

    public function index()
    {
        return DataTables::of(CourseStudent::query()->with(['course', 'student'])->orderBy('created_at', 'desc'))
        ->addColumn('approve', function ($student) {
            if ($student->status_id == 1) {
                $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus" >';
            } else {
                $input = '<input data-action="approve" type="checkbox" class="switch" name="switchstatus" checked>';
            }
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('course', function ($student) {
            return $student->course->name_ar;
        })
        ->addColumn('student', function ($student) {
            return $student->student->first_name . ' ' . $student->student->last_name;
        })
        ->addColumn('mobile', function ($student) {
            return $student->student->mobile;
        })
        ->addColumn('created_at', function ($student) {
            return $student->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'approve', 'course', 'student'])
        ->make(true);
    }

    public function list()
    {
        $students = CourseStudent::orderby('created_at', 'DESC')->get();

        return $this->success($students);
    }
    public function show($id)
    {
        $register = CourseStudent::with(['course', 'course.sections', 'student', 'studentPayments'])->find($id);

        if (!$register) {
            return $this->error(__('main.item_not_found'), 404);
        }

        $register->courseCost = $register->studentPayments->cost;
        $sections = $register->course->sections->collect()->map(function ($section) {
            return $section->id;
        });
        $lessons = Lesson::whereIn('section_id', $sections)->get()->pluck('id');
        $register->lessonsCount = count($lessons);
        $progress = StudentProgress::with(['lesson', 'lesson.section'])
        ->whereIn('lesson_id', $lessons)
        ->where('student_id', $register->student_id)
        ->get();
        $lessonTotalProgress = StudentProgress::with(['lesson', 'lesson.section'])
        ->whereIn('lesson_id', $lessons)
        ->where('student_id', $register->student_id)
        ->distinct()
        ->sum('progress');
        $courseProgress = 0;
        $lessonTotalProgress = $lessonTotalProgress / 100;
        if (count($lessons) !== 0) {
            $courseProgress = ($lessonTotalProgress / count($lessons)) * 100;
        }
        $register->progress = $progress;
        $register->courseProgress = $courseProgress;

        return $this->success($register);
    }

    public function approve($id)
    {
        $register = CourseStudent::find($id);
        if ($register) {
            $register->status_id = ($register->status_id == 1 ? 2 : 1);
            if ($register->status_id == 2) {
                $date_of_register = Carbon::now()->format('Y-m-d');
                $register->updated_at = $date_of_register;
                $register->save();
                return $this->success($register, __('main.register_success'));
            } else {
                $register->save();
                return $this->success($register, __('main.not_register'));
            }
        }
        return $this->error(__('main.item_not_found'), 404);
    }
    public function destroy($id)
    {
        $registered = CourseStudent::find($id);
        $payment = PaymentTransaction::where('registered_course_id', $id)->first();
        if ($payment) {
            $payment->delete();
            $registered->delete();
            return $this->success('', trans('main.register_course_delete_success'));
        } else {
            $registered->delete();
            return $this->success('', trans('main.register_course_delete_success'));
        }
    }
}
