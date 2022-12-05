<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgress extends Model
{
    use HasFactory;

    protected $table = 'student_progress';
    protected $fillable = [
      'student_id',
      'lesson_id',
      'progress',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
