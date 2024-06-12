<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\AcademicYear;
use App\Module;
use App\kafaExamResult;

class kafaExam extends Model
{
    public function academic_year()
    {
        return $this->belongsTo('App\AcademicYear');
    }
    public function module()
    {
        return $this->belongsTo('App\Module');
    }
    public function kafa_exam_results()
    {
        return $this->hasMany('App\kafaExamResult');
    }
}
