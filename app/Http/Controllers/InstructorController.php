<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;

class InstructorController extends Controller
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
        return DataTables::of(Instructor::query()->orderBy('created_at', 'desc'))
        ->addColumn('created_at', function ($instructor) {
            return $instructor->created_at->format('Y-m-d');
        })
        ->addColumn('full_name', function ($instructor) {
            return $instructor->full_name_ar;
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at'])
        ->make(true);
    }

    public function list()
    {
        return $this->success(Instructor::orderby('created_at', 'DESC')->get());
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
            'full_name' => 'required|string|min:1',
            'full_name_ar' => 'required|string|min:1',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'email' => 'required|email|unique:instructors,email',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $instructor = Instructor::create($input);
        $this->createSlug('Instructor', $instructor->id, $instructor->name, 'instructors');

        if ($request->file('image')) {
            $image_name = md5($instructor->id . "app" . $instructor->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/instructors';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $instructor->image =  $image_full_name;
        }
        $instructor->save();
        $this->translate($request, 'Instructor', $instructor->id);
        return $this->success(['instructor' => $instructor], trans('main.instructor_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $instructor = Instructor::find($id);
        return $this->success(['instructor' => $instructor]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $instructor = Instructor::find($id);

        return $this->success(['instructor' => $instructor]);
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
        $instructor = Instructor::find($id);
        if (!$instructor) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
            'full_name' => 'required|string|min:1',
            'full_name_ar' => 'required|string|min:1',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'email' => 'required|email|unique:instructors,email,' . $instructor->id
        ]);
        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $instructor = Instructor::find($id);

        $this->editSlug('Instructor', $instructor->id, $instructor->name, 'instructors');

        $instructor->update($input);
        if ($request->file('image')) {
            $image_name = md5($instructor->id . "app" . $instructor->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/instructors';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $instructor->image =  $image_full_name;
        }
        $instructor->save();
        $this->editTranslation($request, 'Instructor', $instructor->id);

        return $this->success(['instructor' => $instructor], __('main.instructor_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $instructor = Instructor::find($id);
        $courses = Course::where('instructor_id', $id)->get();
        if (count($courses) > 0) {
            return $this->error(trans('main.instructor_has_courses'), 422);
        }
        if ($instructor->image) {
            File::delete(public_path() . "/uploads/instructors" . $instructor->image);
        }
        $instructor->delete();
        return $this->success('', trans('main.instructor_delete_success'));
    }
}
