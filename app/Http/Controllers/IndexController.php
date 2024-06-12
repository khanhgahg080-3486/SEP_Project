<?php

namespace App\Http\Controllers;

use App\AttendanceSession;
use App\Batch;
use App\Student;
use App\StudentEnroll;
use App\kafaExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    private $exam_types = array('T' => 'Theory', 'P' => 'Practical', 'B' => 'Theory and Practical');
    private $exam_pass = array('P' => 'Pass', 'F' => 'Fail', 'AB' => 'Absent');

    public function getIndex()
    {
        return view('index');
    }

    public function getIndexData(Request $request)
    {
        $this->validate($request, [
            'nic' => 'required|min:10|max:12',
        ]);
        $student = Student::where('nic', $request['nic'])->first();


        if (!$student) {
            return redirect()->route('index')->with(['warning' => 'Invalid NIC number. Please check your NIC number and try again.']);
        }
        $student_id = $student->id;
        $enrolls = StudentEnroll::where('student_id', $student_id)->get();

        $results = [];
        $batches = [];
        foreach ($enrolls as $enroll) {
            $batch = Batch::where([['course_id', $enroll->course_id], ['academic_year_id', $enroll->academic_year_id]])->first();
            if (!$batch) {
                return redirect()->route('index')->with(['warning' => 'Invalid Batch Name. Try again.']);
            }
            $batches[] = $batch;
            $result = kafaExamResult::select(
                'module_id',
                'kafa_exams.exam_type',
                DB::raw('max(kafa_exam_results.attempt) as attempt'),
                DB::raw('max(kafa_exam_results.result) as result'),
                DB::raw('max(modules.name) as module_name'),
                DB::raw('max(modules.code) as module_code'),
                DB::raw('max(kafa_exams.exam_date) as exam_date')
            )
                ->leftJoin('kafa_exams', 'kafa_exams.id', '=', 'kafa_exam_results.kafa_exam_id')
                ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
                ->leftJoin('academic_years', 'academic_years.id', '=', 'kafa_exams.academic_year_id')
                ->groupBy('module_id')
                ->groupBy('exam_type')
                ->orderBy('module_id', 'asc')
                ->orderBy('exam_type', 'desc')
                ->where([['student_id', $student_id], ['course_id', $batch->course_id]])
                ->get();
            $results[] = $result;

        }

        //        Attendance Summary Data
        $attendances = [];
        foreach ($enrolls as $enroll) {
            $attendance = AttendanceSession::select(
                'attendance_sessions.module_id',
                'attendance_sessions.academic_year_id',
                DB::raw('count(attendances.id) as total'),
                DB::raw('sum(attendances.is_present) as present'),
                DB::raw('max(modules.name) as module_name'),
                DB::raw('max(modules.code) as module_code')
            )
                ->leftJoin('attendances', 'attendances.attendance_session_id', '=', 'attendance_sessions.id')
                ->leftJoin('modules', 'attendance_sessions.module_id', '=', 'modules.id')
                ->groupBy('module_id')
                ->groupBy('academic_year_id')
                ->groupBy('modules.name')
                ->groupBy('modules.code')
                ->where([['student_id', $student_id], ['modules.course_id', $enroll->course_id]])
                ->get();
            $attendances[] = $attendance;
        }
        //return response()->json(['Attendances'=>$attendances,'batches'=>$batches],200);
        return view('result', ['results' => $results, 'exam_types' => $this->exam_types, 'student' => $student, 'batches' => $batches, 'exam_pass' => $this->exam_pass, 'attendances' => $attendances]);
        //return view('result');
    }

}
