<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    public $fillable = ['full_name', 'email','image','description'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    protected $appends = [
      'full_name_ar','description_ar', 'slug','image_full_path'
    ];

    public function getFullNameArAttribute()
    {
        $translation = Translation::where('model', 'Instructor')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'full_name')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getDescriptionArAttribute()
    {
        $translation = Translation::where('model', 'Instructor')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'description')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getSlugAttribute()
    {
        $slug = Slug::where('model', 'Instructor')
        ->where('row_id', $this->attributes['id'])
        ->first();

        return $slug ? $slug->value : null;
    }

    public function getImageFullPathAttribute()
    {
        return $this->image ? env('APP_URL')  . 'uploads/instructors/' . $this->image : null;
    }
}
