<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;

class LessonController extends Controller
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
        return DataTables::of(Lesson::query()->orderBy('created_at', 'desc'))
        ->addColumn('name', function ($lesson) {
            return $lesson->title_ar;
        })
        ->addColumn('title', function ($lesson) {
            return $lesson->title_ar;
        })
        ->addColumn('section', function ($lesson) {
            return ($lesson->section ? $lesson->section->name_ar : '')  . ' | ' . ($lesson->section ? $lesson->section->course->name_ar : '')  .
            ' | ' .($lesson->section ? $lesson->section->course->name_ar : '') ;
        })
        ->addColumn('created_at', function ($lesson) {
            return $lesson->created_at->format('Y-m-d');
        })
        ->addColumn('free', function ($lesson) {
            if ($lesson->free == 0) {
                $input = '<input data-action="free" type="checkbox" class="switch" name="switchstatus" >';
            } else {
                $input = '<input data-action="free" type="checkbox" class="switch" name="switchstatus" checked>';
            }
            return '<label class="switch">' . $input . '<span class="slider round"></span></label>';
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'section', 'name', 'free'])
        ->make(true);
    }

    public function list()
    {
        $lessons = Lesson::with(['section', 'section.course'])->orderby('created_at', 'DESC')->get();

        return $this->success($lessons);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $courses = Course::orderby('created_at', 'DESC')->get();
        $sections = Section::orderby('created_at', 'DESC')->get();
        return $this->success([
          'sections' => $sections,
          'courses' => $courses
        ]);
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
            'title' => 'required|string|min:1',
            'title_ar' => 'required|string|min:1',
            'video' => 'required|file|mimetypes:video/mp4',
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $lesson = Lesson::create($input);
        $input['slug'] = $this->createSlug('Lesson', $lesson->id, $lesson->title, 'lessons');

        $uploads_folder =  getcwd() . '/uploads/lessons/';
        if (!file_exists($uploads_folder)) {
            mkdir($uploads_folder, 0777, true);
        }
        if ($request->file('video')) {
            return response()->json($request->file('video')->getRealPath());
            $video_name = md5($lesson->id . rand(1, 1000));
            $video_ext = $request->file('video')->getClientOriginalExtension();
            $video_full_name = $video_name . '.' . $video_ext;
            $request->file('video')->move($uploads_folder, $video_name  . '.' . $video_ext);
            $lesson->video =  $video_full_name;
        }
        $lesson->save();
        $this->translate($request, 'Lesson', $lesson->id);

        return $this->success(['lesson' => $lesson], trans('main.lesson_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $lesson = Lesson::find($id);
        return $this->success(['lesson' => $lesson]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $courses = Course::orderby('created_at', 'DESC')->get();
        $sections = Section::orderby('created_at', 'DESC')->get();
        $lesson = Lesson::with([
          'section',
          'section.course',
          'section.course'
        ])->find($id);

        return $this->success([
          'lesson' => $lesson,
          'sections' => $sections,
          'courses' => $courses
        ]);
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
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
          'title' => 'required|string|min:1',
          'title_ar' => 'required|string|min:1',
          'video' => 'nullable|file|mimetypes:video/mp4',
          'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $lesson = Lesson::find($id);

        $this->editSlug('Lesson', $lesson->id, $lesson->title, 'lessons');

        $lesson->update($input);

        $uploads_folder =  getcwd() . '/uploads/lessons/';
        if (!file_exists($uploads_folder)) {
            mkdir($uploads_folder, 0777, true);
        }
        if ($request->file('video')) {
            if ($lesson->video) {
                  File::delete(getcwd() . "/uploads/lessons/" . $lesson->video);
            }
            $video_name = md5($lesson->id . rand(1, 1000));
            $video_ext = $request->file('video')->getClientOriginalExtension();
            $video_full_name = $video_name . '.' . $video_ext;
            $request->file('video')->move($uploads_folder, $video_name  . '.' . $video_ext);
            $lesson->video =  $video_full_name;
        }
        $lesson->save();
        $this->editTranslation($request, 'Lesson', $lesson->id);

        return $this->success(['lesson' => $lesson], __('main.lesson_update_success'));
    }

    public function free($id)
    {
        $lesson = Lesson::find($id);
        if ($lesson) {
            $lesson->free = ($lesson->free == 0 ? 1 : 0);
            $lesson->save();
            return $this->success($lesson, __('main.free_lesson_success'));
        }
        return $this->error(__('main.item_not_found'), 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $lesson = Lesson::find($id);
        $progress = StudentProgress::where('lesson_id', $id)->get();
        if (count($progress) > 0) {
            return $this->error(trans('main.lesson_has_listen'), 422);
        }
        if ($lesson->video) {
            File::delete(getcwd() . "/uploads/lessons/" . $lesson->video);
        }
        $lesson->delete();
        return $this->success('', trans('main.lesson_delete_success'));
    }
}
