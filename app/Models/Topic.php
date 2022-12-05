<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    public $fillable = [
      'name',
      'image',
      'description',
      'sub_category_id'
    ];

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    protected $appends = [
      'name_ar', 'description_ar', 'slug'
    ];

    public function getNameArAttribute()
    {
        $translation = Translation::where('model', 'Topic')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'name')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getDescriptionArAttribute()
    {
        $translation = Translation::where('model', 'Topic')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'description')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getSlugAttribute()
    {
        $slug = Slug::where('model', 'Topic')
        ->where('row_id', $this->attributes['id'])
        ->first();

        return $slug ? $slug->value : null;
    }
}
