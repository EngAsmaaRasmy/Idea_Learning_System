<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
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
        return DataTables::of(Course::query()->orderBy('created_at', 'desc'))
        ->addColumn('subCategory', function ($course) {
            return ($course->subCategory ? $course->subCategory->name_ar : '');
        })
        ->addColumn('hide', function ($course) {
            if ($course->hide_id == 1) {
                $input = '<input data-action="hide" type="checkbox" class="switch" name="switchstatus" >';
            } else {
                $input = '<input data-action="hide" type="checkbox" class="switch" name="switchstatus" checked>';
            }
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->addColumn('created_at', function ($course) {
            return $course->created_at->format('Y-m-d');
        })
        ->addColumn('name', function ($course) {
            return $course->name_ar;
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at','subcategory' ,'name', 'hide'])
        ->make(true);
    }

    public function list()
    {
        $courses = Course::orderby('created_at', 'DESC')->get();

        return $this->success($courses);
    }

    public function sections($id)
    {
        return Section::with('lessons')->where('course_id', $id)->get();
    }

    public function search(Request $request)
    {
        $local = $request->header('Accept-Language');
        $courses = Course::orderby('created_at', 'DESC')->get();
        if ($courses) {
            $courses = $courses->collect()->filter(function ($course) use ($request, $local) {
                if (Str::contains(strtolower($course->name), strtolower($request->get('text'))) || Str::contains($course->name_ar, $request->get('text'))) {
                    if ($local && $local == 'ar') {
                        $course->name = $course->name_ar;
                        $course->description = $course->description_ar;
                        $course->short = $course->short_ar;
                        $course->subCategory->name = $course->subCategory->name_ar;
                        $course->subCategory->category->name = $course->subCategory->category->name_ar;
                        $course->instructor->full_name = $course->instructor->full_name_ar;
                    } else {
                        $course->name = $course->name;
                        $course->description = $course->description;
                        $course->short = $course->short;
                        $course->subCategory->name = $course->subCategory->name;
                        $course->subCategory->category->name = $course->subCategory->category->name;
                        $course->instructor->full_name = $course->instructor->full_name ;
                    }
                    return $course;
                }
            });
        }
        return $this->success($courses);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string|min:1',
            'name_ar' => 'required|string|min:1',
            'cost' => 'required|numeric',
            'instructor_id' => 'required|numeric',
            'number_of_days' => 'nullable|numeric',
            'sub_category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $sections = (isset($input['sections']) ? (is_array($input['sections']) ? $input['sections'] : json_decode($input['sections'])) : []);
        $lessons = (isset($input['lessons']) ? (is_array($input['lessons']) ? $input['lessons'] : json_decode($input['lessons'])) : []);
        $videos = (isset($input['videos']) ? (is_array($input['videos']) ? $input['videos'] : (array) json_decode($input['videos'])) : []);
        $sectionArray = [];
        foreach ($sections as $key => $section) {
            $validator = Validator::make((array) $section, [
                'name' => 'required|string|distinct|min:1',
                'name_ar' => 'required|string|min:1|distinct'
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
        }
        foreach ($lessons as $key => $lesson) {
            $validator = Validator::make((array) $lesson, [
                'title' => 'required|string|min:1',
                'title_ar' => 'required|string|min:1',
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
        }
        $course = Course::create($input);
        $this->createSlug('Course', $course->id, $course->name, 'courses');
        foreach ($sections as $section) {
            $object = Section::create([
                'name' => $section->name,
                'course_id' => $course->id,
            ]);
            $this->translateArray($section, 'Section', $object->id);
            $sectionArray[$section->id] = $object->id;
        }
        $videoItems = array();
        foreach ($lessons as $key => $item) {
            $videoItems[] = $item->video;
            if (!isset($sectionArray[$item->section_id])) {
                return $this->error(_('main.empty_section'), 422);
            }
            $section_id = $sectionArray[$item->section_id];
            $item->free = ($item->free ? 1 : 0);
            $lesson = Lesson::create([
                'section_id' => $section_id,
                'title' => $item->title,
                'free' => $item->free,
                'video' => $item->video
            ]);
            $this->translateArray($item, 'Lesson', $lesson->id);
            $lesson->save();
        }
        $differenceArray = array_diff($videoItems, $videos);
        foreach ($differenceArray as $video) {
            if (File::delete(public_path() . "/uploads/lessons/" . $video)) {
                $media = Media::where('name', $video)->delete();
            }
        }
        if ($request->file('image')) {
            $image_name = md5($course->id . "app" . $course->id . rand(1, 1000));
            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc
            $image_full_name = $image_name . '.' . $image_ext;
            $uploads_folder =  getcwd() . '/uploads/courses/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);
            $course->image =  $image_full_name;
        }
        $course->save();
        $this->translate($request, 'Course', $course->id);

        return $this->success(['course' => $course], trans('main.course_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::with(['courseStudent', 'courseStudent.student'])->find($id);
        $cost = (float) str_replace(',', '', $course->cost);
        $student = CourseStudent::with('student', 'studentPayments')->where('course_id', $id)->orderBy('created_at', 'desc')->get();
        foreach ($student as $student) {
            $student->date_of_register = $student->date_of_register->format('Y-m-d');
        }
        $input['registered'] = CourseStudent::where('course_id', $id)->count();
        $input['expectedAmount'] = number_format((float)$cost * $input['registered']);
        $input['paid'] = CourseStudent::where('course_id', $id)->where('status_id', 2)->count();
        $input['totalPaid'] = number_format((float)$cost * $input['paid']);
        $input['notPaid'] = CourseStudent::where('course_id', $id)->where('status_id', 1)->count();
        $input['residual'] = number_format((float)$cost * $input['notPaid']);
        return $this->success(['course' => $course , 'student' => $student , 'input' => $input]);
    }
    public function filter($id)
    {
         return DataTables::of(CourseStudent::query()->whereHas('studentPayments')->where('course_id', $id)->orderBy('created_at', 'desc'))
        ->addColumn('students', function ($student) {
            $student->where('registered_course_id', $student->id);
             return ($student->student ? $student->student->first_name : ' ');
        })
        ->addColumn('status', function ($student) {
            if ($student->studentPayments) {
                if ($student->studentPayments->payment_status_id == 2) {
                    return 'تم الدفع';
                } else {
                    return ' لم يتم الدفع ';
                }
            } else {
                return ' ';
            }
        })
        ->addColumn('created_at', function ($student) {
            if ($student->studentPayments) {
                return $student->studentPayments->created_at->format('Y-m-d');
            } else {
                return $student->created_at->format('Y-m-d');
            }
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['students', 'status' ,'created_at'])
        ->make(true);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::with([
            'subCategory',
            'subCategory.category',
            'instructor',
            'sections',
            'sections.lessons'
        ])->find($id);
        $cost = (float) str_replace(',', '', $course->cost);
        return $this->success(['course' => $course , 'cost' => $cost ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $course = Course::find($id);
        if (!$course) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
            'name' => 'required|string|min:1',
            'name_ar' => 'required|string|min:1',
            'cost' => 'required|numeric',
            'instructor_id' => 'required|numeric',
            'short' => 'nullable|string|max:150',
            'number_of_days' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $course = Course::find($id);
        if (!$course) {
            return $this->error(_('main.course_not_found'), 422);
        }
        $sections = (isset($input['sections']) ? (is_array($input['sections']) ? $input['sections'] : json_decode($input['sections'])) : []);
        $lessons = (isset($input['lessons']) ? (is_array($input['lessons']) ? $input['lessons'] : json_decode($input['lessons'])) : []);
        $videos = (isset($input['videos']) ? (is_array($input['videos']) ? $input['videos'] : (array) json_decode($input['videos'])) : []);
        foreach ($sections as $section) {
            $validator = Validator::make((array) $section, [
                'name' => 'required|string',
                'name_ar' => 'required|string'
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
        }
        foreach ($lessons as $key => $lesson) {
            $validator = Validator::make((array) $lesson, [
                'title' => 'required|string',
                'title_ar' => 'required|string',
            ]);
            if ($validator->fails()) {
                $message = implode("\n", $validator->errors()->all());
                return $this->error($message, 422, $validator->errors());
            }
        }
        $course->update($input);
        if ($request->file('image')) {
            if ($course->image) {
                File::delete(public_path() . "/uploads/" . $course->image);
            }
            $image_name = md5($course->id . "app" . $course->id . rand(1, 1000));
            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc
            $image_full_name = $image_name . '.' . $image_ext;
            $uploads_folder =  getcwd() . '/uploads/courses/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);
            $course->image =  $image_full_name;
        }
        $this->editTranslation($request, 'Course', $course->id);
        $this->editSlug('Course', $course->id, $course->name, 'courses');
        $course->save();
        $sectionArray = [];
        foreach ($sections as $section) {
            $object = Section::find($section->id);
            if ($object) {
                $object->update([
                    'name' => $section->name,
                ]);
                $this->editTranslationArray($section, 'Section', $object->id);
            } else {
                $object = Section::create([
                    'name' => $section->name,
                    'course_id' => $course->id,
                ]);
                $this->translateArray($section, 'Section', $object->id);
            }
            $sectionArray[$section->id] = $object->id;
        }
        $videoItems = array();
        foreach ($lessons as $key => $item) {
            $videoItems[] = $item->video;
            $lesson = Lesson::find($item->id);
            if ($lesson) {
                $item->free = ($item->free ? 1 : 0);
                $lesson->update([
                    'title' => $item->title,
                    'free' => $item->free,
                    'video' => $item->video,
                ]);
                $this->editTranslationArray($item, 'Lesson', $lesson->id);
            } else {
                if (!isset($sectionArray[$item->section_id])) {
                    return $this->error(_('main.empty_section'), 422);
                }
                $section_id = $sectionArray[$item->section_id];
                $item->free = ($item->free ? 1 : 0);
                $lesson = Lesson::create([
                    'section_id' => $section_id,
                    'title' => $item->title,
                    'free' => $item->free,
                    'video' => $item->video,
                ]);
                $this->translateArray($item, 'Lesson', $lesson->id);
            }
            $lesson->save();
        }
        $differenceArray = array_diff($videoItems, $videos);
        // return $this->success([$differenceArray,$videoItems, $videos, $lessons]);
        foreach ($differenceArray as $video) {
            if (File::delete(public_path() . "/uploads/lessons/" . $video)) {
                $media = Media::where('name', $video)->delete();
            }
        }
        return $this->success(['course' => $course], trans('main.course_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::find($id);
        $registered = CourseStudent::where('course_id', $id)->get();
        if (count($registered) > 0) {
            return $this->error(trans('main.course_has_registered_student'), 422);
        }
        $sections = Section::where('course_id', $id)->get();
        if (count($sections) > 0) {
            return $this->error(trans('main.course_has_sections'), 422);
        }
        if ($course->image) {
            File::delete(public_path() . "/uploads/" . $course->image);
        }
        $course->delete();
        return $this->success('', trans('main.course_delete_success'));
    }
    public function hide($id)
    {
        $course = Course::find($id);
        if ($course) {
            $course->hide_id = ($course->hide_id == 1 ? 2 : 1);
            if ($course->hide_id == 2) {
                $course->save();
                return $this->success($course, __('main.hide_success'));
            } else {
                $course->save();
                return $this->success($course, __('main.not_hide'));
            }
        }
        return $this->error(__('main.item_not_found'), 404);
    }
}
