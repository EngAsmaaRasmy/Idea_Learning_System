<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'status_id', // approved, pending
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function studentPayments()
    {
        return $this->hasOne('App\Models\PaymentTransaction', 'registered_course_id');
    }

    protected $appends = ['date_of_register'];
    public function getDateOfRegisterAttribute()
    {
        return $this->updated_at;
    }
}
