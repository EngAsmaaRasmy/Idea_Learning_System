<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\SubCategory;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\TranslationTrait;
use DataTables;
use Validator;

class SectionController extends Controller
{
    use ApiResponser;
    use TranslationTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return DataTables::of(Section::query()->orderBy('created_at', 'desc'))
        ->addColumn('name', function ($section) {
            return $section->name_ar;
        })
        ->addColumn('course', function ($section) {
            return $section->course->name_ar;
        })
        ->addColumn('created_at', function ($section) {
            return $section->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'course', 'name'])
        ->make(true);
    }

    public function list()
    {
        $sections = Section::with(['lessons', 'course'])
        ->orderby('created_at', 'DESC')->get();

        return $this->success($sections);
    }
    public function lessons($id)
    {
        $lessons = Lesson::with(['section', 'section.course'])
        ->where('section_id', $id)
        ->get();

        return $this->success($lessons);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::orderby('created_at', 'DESC')->get();
        $subCategories = SubCategory::orderby('created_at', 'DESC')->get();
        $courses = Course::orderby('created_at', 'DESC')->get();
        return $this->success([
            'categories' => $categories,
            'subCategories' => $subCategories,
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
            'name' => 'required|string|min:1',
            'name_ar' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $section = Section::create($input);

        $section->save();
        $this->translate($request, 'Section', $section->id);

        return $this->success(['section' => $section], trans('main.section_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $section = Section::find($id);
        return $this->success(['section' => $section]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $categories = Category::orderby('created_at', 'DESC')->get();
        $subCategories = SubCategory::orderby('created_at', 'DESC')->get();
        $courses = Course::orderby('created_at', 'DESC')->get();
        $section = Section::with([
            'course',
            'course.subCategory',
            'course.subCategory.category'
        ])->find($id);

        return $this->success([
            'section' => $section,
            'categories' => $categories,
            'subCategories' => $subCategories,
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
        $section = Section::find($id);
        if (!$section) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
            'name' => 'required|string|min:1',
            'name_ar' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $section = Section::find($id);

        $section->update($input);

        $section->save();
        $this->editTranslation($request, 'Section', $section->id);

        return $this->success(['section' => $section], __('main.section_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $section = Section::find($id);
        $lessons = Lesson::where('section_id', $id)->get();
        if (count($lessons) > 0) {
            return $this->error(trans('main.section_has_lessons'), 422);
        }
        $section->delete();
        return $this->success('', trans('main.section_delete_success'));
    }
}
