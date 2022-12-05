<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteProfile extends Model
{
    use HasFactory;

    protected $fillable = [
      'about',
      'email',
      'mobile',
      'address'
    ];

    protected $appends = [
      'address_ar', 'about_ar'
    ];

    public function getAddressArAttribute()
    {
        $translation = Translation::where('model', 'SiteProfile')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'address')
        ->first();

        return $translation ? $translation->value : null;
    }

    public function getAboutArAttribute()
    {
        $translation = Translation::where('model', 'SiteProfile')
        ->where('row_id', $this->attributes['id'])
        ->where('field', 'about')
        ->first();

        return $translation ? $translation->value : null;
    }
}
