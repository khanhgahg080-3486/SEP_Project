<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Batch;
use App\StudentEnroll;
use App\kafaExam;

class AcademicYear extends Model
{
    public function batches()
    {
        return $this->hasMany('App\Batch');
    }
    public function student_enrolls()
    {
        return $this->hasMany('App\StudentEnroll');
    }
    public function kafa_exams()
    {
        return $this->hasMany('App\kafaExam');
    }
    public function attendance_sessions()
    {
        return $this->hasMany('App\AttendanceSession');
    }
}
