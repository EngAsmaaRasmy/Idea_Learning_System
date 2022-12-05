<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;


    public $fillable = [
      'title',
      'description',
      'image',
      'video',
      'free',
      'section_id'
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    protected $appends = [
      'title_ar', 'description_ar', 'slug', 'video_full_path'
    ];

    public function getTitleArAttribute()
    {
        $translation = Translation::where('model', 'Lesson')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'title')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getDescriptionArAttribute()
    {
        $translation = Translation::where('model', 'Lesson')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'description')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getSlugAttribute()
    {
        $slug = Slug::where('model', 'Lesson')
        ->where('row_id', $this->attributes['id'])
        ->first();

        return $slug ? $slug->value : null;
    }

    public function getVideoFullPathAttribute()
    {
        return $this->video ? env('APP_URL')  . 'uploads/lessons/' . $this->video : null;
    }
}
