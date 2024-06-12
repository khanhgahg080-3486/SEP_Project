<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Course;
use App\kafaExam;

class Module extends Model
{
    public function course()
    {
        return $this->belongsTo('App\Course');
    }
    public function kafa_exams()
    {
        return $this->hasMany('App\kafaExam');
    }
    public function employees()
    {
        return $this->belongsToMany('App\Employee', 'employee_module', 'module_id', 'employee_id');
    }
    public function getTeacher($mid, $aid)
    {
        return $this->employees()
            ->where([['module_id', $mid], ['academic_year_id', $aid]])
            ->first();
    }
    public function attendance_sessions()
    {
        return $this->hasMany('App\AttendanceSession');
    }
}
