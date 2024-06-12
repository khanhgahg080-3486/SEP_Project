<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class kafaExamResult extends Model
{
    public function student()
    {
        return $this->belongsTo('App\Student');
    }
    public function kafa_exam()
    {
        return $this->belongsTo('App\kafaExam');
    }
}
