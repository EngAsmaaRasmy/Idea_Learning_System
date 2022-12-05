<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\Instructor;
use App\Models\Lesson;
use App\Models\Student;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use ApiResponser;
    use SlugTrait;

    //
    public function index(Request $request)
    {
        $local = $request->header('Accept-Language');
        $newCourses = Course::with(['subCategory', 'subCategory.category', 'instructor', 'sections'])
        ->where('hide_id', 1)->latest()->limit(3)->get();
        $courses = Course::with(['subCategory', 'subCategory.category', 'instructor', 'sections'])
        ->where('hide_id', 1)->orderby('created_at', 'DESC')->limit(6)->get();
        $categories = Category::get();
        if ($local && $local == 'ar') {
            $courses->each(function ($course) {
                $course->name = ($course->name_ar ?? $course->name);
                $course->description = ($course->description_ar ?? $course->description);
                $course->short = ($course->short_ar ?? $course->short);
                $course->subCategory->name = ($course->subCategory->name_ar ?? $course->subCategory->name);
                $course->subCategory->category->name = ($course->subCategory->category->name_ar ?? $course->subCategory->category->name);
                $course->instructor->full_name = ($course->instructor->full_name_ar ?? $course->instructor->full_name);
                return $course;
            });
            $newCourses->each(function ($newCourse) {
                $newCourse->name = ($newCourse->name_ar ?? $newCourse->name);
                $newCourse->description = ($newCourse->description_ar ?? $newCourse->description);
                $newCourse->short = ($newCourse->short_ar ?? $newCourse->short);
                $newCourse->subCategory->name = ($newCourse->subCategory->name_ar ?? $newCourse->subCategory->name);
                $newCourse->subCategory->category->name = ($newCourse->subCategory->category->name_ar ?? $newCourse->subCategory->category->name);
                $newCourse->instructor->full_name = ($newCourse->instructor->full_name_ar ?? $newCourse->instructor->full_name);
                return $newCourse;
            });
        }

        return $this->success([
            'newCourses' => $newCourses,
            'courses' => $courses,
            'categories' => $categories
        ]);
    }

    public function course($slug, Request $request)
    {
        $local = $request->header('Accept-Language');
        $id = $this->getRowId('Course', $slug);
        if (!$id) {
            return $this->error(__('main.item_not_found'), 404);
        }
        $course = Course::with([
            'subCategory',
            'subCategory.category',
            'instructor',
            'sections',
            'sections.lessons'
        ])->find($id);
        if ($local && $local == 'ar') {
            $course->name = ($course->name_ar ?? $course->name);
            $course->description = ($course->description_ar ?? $course->description);
            $course->short = ($course->short_ar ?? $course->short );
            $course->subCategory->name = ($course->subCategory->name_ar ?? $course->subCategory->name);
            $course->subCategory->category->name = ($course->subCategory->category->name_ar ?? $course->subCategory->category->name);
            $course->instructor->full_name = ($course->instructor->full_name_ar ?? $course->instructor->full_name);
            $course->instructor->description = ($course->instructor->description_ar ?? $course->instructor->description);
            $course->sections->each(function ($section) {
                $section->name = ($section->name_ar ?? $section->name);
                $section->description = ($section->description_ar ?? $section->description);
                if ($section->lessons) {
                    $section->lessons->each(function ($lesson) {
                        $lesson->title = ($lesson->title_ar ?? $lesson->title);
                        $lesson->description = ($lesson->description_ar ?? $lesson->description);
                        return $lesson;
                    });
                }
                return $section;
            });
        }

        return $this->success(['course' => $course]);
    }

    public function lesson($id)
    {
        if (Lesson::find($id)) {
            return $this->success(Lesson::find($id));
        }
        return $this->error(__('main.item_not_found'), 404);
    }

    public function courses(Request $request)
    {
        $courses = Course::with(['instructor', 'sections'])
        ->where('hide_id', 1)->orderby('created_at', 'DESC')->limit(30)->get();
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            $courses->each(function ($course) {
                $course->name = ($course->name_ar ?? $course->name);
                $course->description = ($course->description_ar ?? $course->description);
                $course->short = ($course->short_ar ?? $course->short);
                $course->instructor->full_name = ($course->instructor->full_name_ar ?? $course->instructor->full_name);
                return $course;
            });
        }

        return $this->success($courses);
    }

    public function instructors(Request $request)
    {
        $instructors = Instructor::with(['courses'])->orderby('created_at', 'DESC')->get();
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            $instructors->each(function ($instructors) {
                $instructors->full_name = ($instructors->full_name_ar ?? $instructors->full_name);
                $instructors->description = ($instructors->description_ar ??  $instructors->description);
                foreach ($instructors->courses as $course) {
                    $course->name = ($course->name_ar ?? $course->name);
                }
                return $instructors;
            });
        }

        return $this->success($instructors);
    }

    public function instructor($id, Request $request)
    {
        $local = $request->header('Accept-Language');
        $instructor = Instructor::with(['courses' => function ($courses) {
            return $courses->where('hide_id', 1)->orderby('created_at', 'DESC')->limit(4);
        }])->find($id);
        if ($local && $local == 'ar') {
            $instructor->full_name = ($instructor->full_name_ar ?? $instructor->full_name);
            $instructor->description = ($instructor->description_ar ??  $instructor->description);
            foreach ($instructor->courses as $course) {
                $course->name = ($course->name_ar ?? $course->name);
            };
            return $this->success(['instructor' => $instructor]);
        };
        return $this->success(['instructor' => $instructor]);
    }
    public function countries(Request $request)
    {
        $local = $request->header('Accept-Language');
        if ($local && $local == 'en') {
            $countries = Country::orderby('name_en')->get();
            $countries->each(function ($country) {
                $url = env('APP_URL');
                $country->flag = $url . 'flags/' . strtolower($country->code) . '.svg';
                return $country;
            });
            return $this->success($countries);
        } else {
            $countries = Country::orderby('name_ar')->get();
            $countries->each(function ($country) {
                $url = env('APP_URL');
                $country->flag = $url . 'flags/' . strtolower($country->code) . '.svg';
                return $country;
            });
            return $this->success($countries);
        }
    }

    public function statistics()
    {
        $input['students_courses'] = CourseStudent::where('status_id', 2)->count();
        $input['students'] = Student::where('verified', 1)->where('blocked', 0)->count();
        $input['courses'] = Course::where('hide_id', 1)->count();
        $input['categories'] = Category::count();
        return $this->success($input);
    }
}
