<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'image',
        'start_date',
        'end_date',
        'lesson_number',
        'total_hours',
        'instructor_id',
        'sub_category_id',
        'short',
        'number_of_days',
        'review',
    ];
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
    public function sections()
    {
        return $this->hasMany(Section::class);
    }
    public function courseStudent()
    {
        return $this->hasMany(CourseStudent::class);
    }

    public function certificate()
    {
        return $this->hasMany(CertificateRequest::class);
    }

    protected $appends = [
        'name_ar', 'description_ar', 'short_ar', 'slug', 'image_full_path'
    ];

    public function getNameArAttribute()
    {
        $translation = Translation::where('model', 'Course')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'name')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getDescriptionArAttribute()
    {
        $translation = Translation::where('model', 'Course')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'description')
        ->first();

        return $translation ? $translation->value : null;
    }
    public function getShortArAttribute()
    {
        $translation = Translation::where('model', 'Course')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'short')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getSlugAttribute()
    {
        $slug = Slug::where('model', 'Course')
        ->where('row_id', $this->attributes['id'])
        ->first();

        return $slug ? $slug->value : null;
    }

    public function getImageFullPathAttribute()
    {
        return $this->image ? env('APP_URL')  . 'uploads/courses/' . $this->image : null;
    }

    public function getCostAttribute($cost)
    {
        return  number_format($cost);
    }
}