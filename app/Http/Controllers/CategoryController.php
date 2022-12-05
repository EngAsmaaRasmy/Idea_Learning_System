<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Illuminate\Support\Facades\File;
use Validator;

class CategoryController extends Controller
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
        return DataTables::of(Category::query()->orderBy('created_at', 'DESC'))
        ->addColumn('created_at', function ($category) {
            return $category->created_at->format('Y-m-d');
        })
        ->addColumn('name', function ($category) {
            return $category->name_ar;
        })
        ->addColumn('icon', function ($category) {
            return '<img src="' . $category->icon_full_path . '" border="0" width="40" class="img-rounded" align="center"/>';
        })
        ->editColumn('id', '{{$id}}')
        ->rawColumns(['created_at' ,'name' ,'icon'])
        ->make(true);
    }

    public function list()
    {
        $categories = Category::with(['subCategories', 'subCategories.courses'])
        ->orderby('created_at', 'DESC')->get();

        return $this->success($categories);
    }
    public function subCategories(Request $request, $id)
    {
        $local = $request->header('Accept-Language');
        $subCategories = SubCategory::with(['category', 'courses'])
        ->where('category_id', $id)
        ->get();
        if ($subCategories) {
            $subCategories = $subCategories->each(function ($subCategory) use ($local) {
                if ($local && $local == 'ar') {
                    $subCategory->name = $subCategory->name_ar;
                    $subCategory->category->name = $subCategory->category->name_ar;
                    foreach ($subCategory->courses as $course) {
                        $course->name = $course->name_ar;
                    }
                }
            });
        }
        return $this->success($subCategories);
    }
    public function courses(Request $request, $slug)
    {
        $local = $request->header('Accept-Language');
        $id = $this->getRowId('Category', $slug);
        if (!$id) {
            return $this->error(__('main.item_not_found'), 404);
        }
        $courses = null;
        $category = Category::find($id);
        if ($local && $local == 'ar') {
            $category->name = $category->name_ar;
        } else {
            $category->name = $category->name;
        }
        $subCategories = SubCategory::where('category_id', $id)
        ->pluck('id');
        $courses = Course::with(['subCategory'])->where('hide_id', 1)->whereIn('sub_category_id', $subCategories)->get();
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
                    $course->short = $course->short;
                    $course->subCategory->name = $course->subCategory->name;
                    $course->subCategory->category->name = $course->subCategory->category->name;
                    $course->instructor->full_name = $course->instructor->full_name;
                }
            });
        }

        $category->courses = $courses;
        return $this->success($category);
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
            'name' => 'required|string|unique:categories,name',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $category = Category::create($input);
        $input['slug'] = $this->createSlug('Category', $category->id, $category->name, 'categories');

        if ($request->file('image')) {
            $image_name = md5($category->id . "app" . $category->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $category->image =  $image_full_name;
        }
        if ($request->file('icon')) {
            $icon_name = md5($category->id . "app" . $category->id . rand(1, 1000));
            $icon_ext = $request->file('icon')->getClientOriginalExtension(); // example: png, jpg ... etc
            $icon_full_name = $icon_name . '.' . $icon_ext;
            $uploads_folder =  getcwd() . '/uploads/icons/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('icon')->move($uploads_folder, $icon_name  . '.' . $icon_ext);
            $category->icon =  $icon_full_name;
        }
        $category->save();
        $this->translate($request, 'Category', $category->id);

        return $this->success(['category' => $category], trans('main.category_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::find($id);
        return $this->success(['category' => $category]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = Category::find($id);

        return $this->success(['category' => $category]);
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
        $category = Category::find($id);
        if (!$category) {
            return $this->error(__('main.not_found'), 404);
        }
        $validator = Validator::make($input, [
            'name' => 'required|string|unique:categories,name,' . $category->id
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $category = Category::find($id);

        $this->editSlug('Category', $category->id, $category->name, 'categories');

        $category->update($input);

        if ($request->file('image')) {
            $image_name = md5($category->id . "app" . $category->id . rand(1, 1000));

            $image_ext = $request->file('image')->getClientOriginalExtension(); // example: png, jpg ... etc

            $image_full_name = $image_name . '.' . $image_ext;

            $uploads_folder =  getcwd() . '/uploads/';

            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }


            $request->file('image')->move($uploads_folder, $image_name  . '.' . $image_ext);

            $category->image =  $image_full_name;
        }
        if ($request->file('icon')) {
            $icon_name = md5($category->id . "app" . $category->id . rand(1, 1000));
            $icon_ext = $request->file('icon')->getClientOriginalExtension(); // example: png, jpg ... etc
            $icon_full_name = $icon_name . '.' . $icon_ext;
            $uploads_folder =  getcwd() . '/uploads/icons/';
            if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
            }
            $request->file('icon')->move($uploads_folder, $icon_name  . '.' . $icon_ext);
            $category->icon =  $icon_full_name;
        }
        $category->save();
        $this->editTranslation($request, 'Category', $category->id);

        return $this->success(['category' => $category], __('main.category_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        $subCategory = SubCategory::where('category_id', $id)->get();
        if (count($subCategory) > 0) {
            return $this->error(trans('main.category_has_sub_category'), 422);
        }
        if ($category->image) {
            File::delete(public_path() . "/uploads/" . $category->image);
        }
        if ($category->icon) {
            File::delete(public_path() . "/uploads/icons" . $category->icon);
        }
        $category->delete();
        return $this->success('', trans('main.category_delete_success'));
    }
}
