<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentTransaction extends Model
{
    use HasFactory;
    
    public $fillable = [
        'payment_method_id',
        'registered_course_id',
        'voucher_id',
        'cost',
        'payment_status_id',
      ];
      public function courseStudent()
      {
          return $this->belongsTo(CourseStudent::class, 'registered_course_id');
      }
      protected $appends = ['payment_number' ,'date_of_payment'];
      public function getPaymentNumberAttribute()
      {
        return str_pad($this->id, 5, "123", STR_PAD_LEFT);
      }
      public function getDateOfPaymentAttribute()
      {
        return $this->updated_at;
      }
}