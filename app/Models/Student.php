<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public $fillable = [
      'first_name',
      'last_name',
      'email',
      'mobile',
      'password',
      'otp',
      'verified',
      'blocked',
      'token',
      'phone_key',
      'image',
      'full_name',
      'full_name_ar',
    ];

    public function courses()
    {
        return $this->hasMany(CourseStudent::class);
    }
    public function certificate()
    {
        return $this->hasMany(CertificateRequest::class);
    }
    public function courseStudent()
    {
        return $this->hasMany(CourseStudent::class);
    }
    public function payments()
    {
        return $this->hasManyThrough(
            'App\Models\PaymentTransaction',
            'App\Models\CourseStudent',
            'student_id',
            'registered_course_id',
            'id',
            'id'
        );
    }
    protected $appends = [
       'image_full_path',
    ];
    public function getImageFullPathAttribute()
    {
        return $this->image ? env('APP_URL')  . 'uploads/students/' . $this->image : null;
    }
}
