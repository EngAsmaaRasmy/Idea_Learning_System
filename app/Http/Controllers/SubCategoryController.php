<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;

class SubCategoryController extends Controller
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
        return DataTables::of(SubCategory::query()->orderBy('created_at', 'desc'))
        ->addColumn('category', function ($subCategory) {
            return ($subCategory->category ? $subCategory->category->name_ar : '');
        })
        ->addColumn('created_at', function ($subCategory) {
            return $subCategory->created_at->format('Y-m-d');
        })
        ->addColumn('name', function ($subCategory) {
            return $subCategory->name_ar;
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at', 'category' ,'name'])
        ->make(true);
    }

    public function list()
    {
        $subCategories = SubCategory::orderby('created_at', 'DESC')->get();

        return $this->success($subCategories);
    }

    public function courses(Request $request, $slug)
    {
        $local = $request->header('Accept-Language');
        $id = $this->getRowId('SubCategory', $slug);
        if (!$id) {
            return $this->error(__('main.item_not_found'), 404);
        }
        $courses = null;
        $subCategory = SubCategory::with('category')->find($id);
        if ($local && $local == 'ar') {
            $subCategory->name = $subCategory->name_ar;
            $subCategory->category->name = $subCategory->category->name_ar;
        } else {
            $subCategory->name = $subCategory->name;
            $subCategory->category->name = $subCategory->category->name;
        }
        $courses = Course::with(['subCategory'])->where('hide_id', 1)->where('sub_category_id', $subCategory->id)->get();
        if ($courses) {
            $courses = $courses->each(function ($course) use ($local) {
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
                    $course->short = $course->short_ar;
                    $course->subCategory->name = $course->subCategory->name;
                    $course->subCategory->category->name = $course->subCategory->category->name;
                    $course->instructor->full_name = $course->instructor->full_name;
                }
            });
        }
        $subCategory->courses = $courses;
        return $this->success($subCategory);
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
            'name' => 'required|string|unique:sub_categories,name',
            'name_ar' => 'required|string|min:1',
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $subCategory = SubCategory::create($input);
        $input['slug'] = $this->createSlug('SubCategory', $subCategory->id, $subCategory->name, 'subCategories');

        if ($request->file('image')) {
            $image_name = md5($subCategory->id . "app" . $subCategory->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $subCategory->image =  $image_full_name;
        }
        $subCategory->save();
        $this->translate($request, 'SubCategory', $subCategory->id);

        return $this->success(['subCategory' => $subCategory], trans('main.subCategory_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subCategory = SubCategory::with('category')->find($id);
        return $this->success(['subCategory' => $subCategory]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subCategory = SubCategory::with('category')->find($id);

        return $this->success(['subCategory' => $subCategory]);
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
       // $input = $request->all();
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|String|unique:sub_categories,name,' . $subCategory->id,
            'name_ar' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $subCategory->update($request->all());
        $subCategory = SubCategory::find($id);
        $this->editSlug('SubCategory', $subCategory->id, $subCategory->name, 'subCategories');
        if ($request->file('image')) {
            $image_name = md5($subCategory->id . "app" . $subCategory->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $subCategory->image =  $image_full_name;
        }
        $subCategory->save();
        $this->editTranslation($request, 'SubCategory', $subCategory->id);
        return $this->success(['subCategory' => $subCategory], __('main.subCategory_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        $courses = Course::where('sub_category_id', $id)->get();
        if (count($courses) > 0) {
            return $this->error(trans('main.subCategory_has_topics'), 422);
        }
        if ($subCategory->image) {
            File::delete(public_path() . "/uploads/" . $subCategory->image);
        }
        $subCategory->delete();
        return $this->success('', trans('main.subCategory_delete_success'));
    }
}
