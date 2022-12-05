<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;

class TopicController extends Controller
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
        return DataTables::of(Topic::query()->orderBy('created_at', 'desc'))
        ->addColumn('subCategory', function ($topic) {
            return ($topic->subCategory ? $topic->subCategory->name_ar : '');
        })
        ->addColumn('name', function ($topic) {
            return $topic->name_ar;
        })
        ->addColumn('created_at', function ($topic) {
            return $topic->created_at->format('Y-m-d');
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'subCategory'])
        ->make(true);
    }

    public function list()
    {
        $subCategories = Topic::orderby('created_at', 'DESC')->get();

        return $this->success($subCategories);
    }
    public function courses(Request $request, $slug)
    {
        $local = $request->header('Accept-Language');
        $id = $this->getRowId('Topic', $slug);
        if (!$id) {
            return $this->error(__('main.item_not_found'), 404);
        }
        $courses = null;
        $topic = Topic::with(['subCategory', 'subCategory.category'])->find($id);
        if ($local && $local == 'ar') {
            $topic->name = $topic->name_ar;
            $topic->subCategory->name = $topic->subCategory->name_ar;
            $topic->subCategory->category->name = $topic->subCategory->category->name_ar;
        }
        $courses = Course::with(['topic', 'topic.subCategory'])->where('topic_id', $topic->id)->get();
        if ($courses) {
            $courses = $courses->each(function ($course) use ($local) {
                if ($local && $local == 'ar') {
                    $course->name = $course->name_ar;
                    $course->description = $course->description_ar;
                    $course->short = $course->short_ar;
                    $course->topic->name = $course->topic->name_ar;
                    $course->topic->subCategory->name = $course->topic->subCategory->name_ar;
                    $course->topic->subCategory->category->name = $course->topic->subCategory->category->name_ar;
                    $course->instructor->full_name = $course->instructor->full_name_ar;
                }
            });
        }

        $topic->courses = $courses;
        return $this->success($topic);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'name' => 'required|string|unique:topics,name',
            'name_ar' => 'required|string|min:1',
            'sub_category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $topic = Topic::create($input);
        $input['slug'] = $this->createSlug('Topic', $topic->id, $topic->name, 'topics');

        if ($request->file('image')) {
            $image_name = md5($topic->id . "app" . $topic->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $topic->image =  $image_full_name;
        }
        $topic->save();
        $this->translate($request, 'Topic', $topic->id);

        return $this->success(['topic' => $topic], trans('main.topic_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $topic = Topic::with('subCategory')->find($id);
        return $this->success(['topic' => $topic]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $topic = Topic::with(['subCategory', 'subCategory.category'])->find($id);

        return $this->success(['topic' => $topic]);
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
        $topic = Topic::find($id);
        if (!$topic) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
            'name' => 'required|string|unique:topics,name,' . $topic->id,
            'name_ar' => 'required|string|min:1',
            'sub_category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $topic = Topic::find($id);

        $this->editSlug('Topic', $topic->id, $topic->name, 'topics');

        $topic->update($input);

        if ($request->file('image')) {
            $image_name = md5($topic->id . "app" . $topic->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $topic->image =  $image_full_name;
        }
        $topic->save();
        $this->editTranslation($request, 'Topic', $topic->id);

        return $this->success(['topic' => $topic], __('main.topic_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $topic = Topic::find($id);
        $courses = Course::where('topic_id', $id)->get();
        if (count($courses) > 0) {
            return $this->error(trans('main.topic_has_courses'), 422);
        }
        if ($topic->image) {
            File::delete(public_path() . "/uploads/" . $topic->image);
        }
        $topic->delete();
        return $this->success('', trans('main.topic_delete_success'));
    }
}
