<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\Batch;
use App\Course;
use App\Employee;
use App\Module;
use App\Student;
use App\StudentEnroll;
use App\kafaExam;
use App\kafaExamResult;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class kafaExamController extends Controller
{
    private $semesters = array('1' => 'Semester 1', '2' => 'Semester 2');
    private $exams = array('T' => 'Theory', 'P' => 'Practical');

    public function getkafaExams()
    {
        $courses = Course::orderBy('code', 'asc')->get();
        $kafaexams = kafaExam::orderBy('academic_year_id', 'desc')
            ->orderBy('module_id', 'asc')
            ->paginate(20);
        return view('examination.kafa_exams', ['courses' => $courses, 'kafaexams' => $kafaexams, 'semesters' => $this->semesters, 'exams' => $this->exams]);
    }

    public function getkafaExamsByCourseBatch(Request $request)
    {
        $courses = Course::orderBy('code', 'asc')->get();
        $this->validate($request, ['batch_couese_id' => 'required',]);
        $batch = Batch::where('id', $request['batch_id'])->first();
        $kafaexams = kafaExam::orderBy('academic_year_id', 'desc')
            ->select(
                'kafa_exams.id as id',
                'kafa_exams.module_id as module_id',
                'kafa_exams.academic_year_id as academic_year_id',
                'kafa_exams.number_pass as number_pass',
                'kafa_exams.number_students as number_students',
                'kafa_exams.exam_type as exam_type',
                'kafa_exams.exam_date as exam_date',
                'modules.course_id as course_id'
            )
            ->where([['course_id', $request['batch_couese_id']], ['academic_year_id', $batch->academic_year_id]])
            ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
            ->orderBy('module_id', 'asc')
            ->paginate(20);
        return view('examination.kafa_exams', ['courses' => $courses, 'kafaexams' => $kafaexams, 'semesters' => $this->semesters, 'exams' => $this->exams]);
    }

    public function getkafaExamsResults($id)
    {
        $kafaexam = kafaExam::select(
            'kafa_exams.id as id',
            'kafa_exams.module_id as module_id',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.number_pass as number_pass',
            'kafa_exams.number_students as number_students',
            'kafa_exams.exam_type as exam_type',
            'kafa_exams.exam_date as exam_date',
            'kafa_exams.exam_time as exam_time',
            'modules.course_id as course_id'
        )->where('kafa_exams.id', $id)->leftjoin('modules', 'modules.id', '=', 'kafa_exams.module_id')->first();
        $batch = Batch::where([['academic_year_id', $kafaexam->academic_year_id], ['course_id', $kafaexam->course_id]])->first();
        $students = kafaExamResult::leftJoin('students', 'students.id', '=', 'kafa_exam_results.student_id')
            ->select('student_id as id', "reg_no", "shortname", "attempt", "result")
            ->distinct(['student_id', 'attempt'])
            ->where([['kafa_exam_id', $id]])
            ->orderBy('reg_no', 'asc')
            ->orderBy('attempt', 'desc')
            ->get();
        //return response()->json(['kafaexam'=>$kafaexam,'student'=>$batch],200);
        return view('examination.kafa_exam_results', ['students' => $students, 'kafaexam' => $kafaexam, 'semesters' => $this->semesters, 'exams' => $this->exams, 'batch' => $batch]);
    }

    public function getkafaExamCreate()
    {
        $courses = Course::orderBy('name', 'asc')->get();
        $academicyears = AcademicYear::orderBy('name', 'desc')->paginate(20);
        return view('examination.kafa_exam', ['academicyears' => $academicyears, 'courses' => $courses, 'semesters' => $this->semesters, 'exams' => $this->exams]);
    }

    public function postkafaExamCreate(Request $request)
    {
        $this->validate($request, [
            'modules' => 'required',
            'academic_year_id' => 'required',
            'exam_type' => 'required',
            'exam_date' => 'required|date',
            'exam_time' => 'required'
        ]);
        $module = Module::find($request['modules']);
        if (!$module) {
            return null;
        }
        $ay = AcademicYear::find($request['academic_year_id']);
        if (!$ay) {
            return null;
        }
        $kafa_exam = kafaExam::where([['module_id', $request['modules']], ['academic_year_id', $request['academic_year_id']], ['exam_type', $request['exam_type']]])->first();
        if ($kafa_exam) {
            return redirect()->back()->with(['warning' => 'Exam was already created. Please check your data!']);
        }
        $te = new kafaExam();
        $te->module_id = $request['modules'];
        $te->academic_year_id = $request['academic_year_id'];
        $te->exam_type = $request['exam_type'];
        $te->exam_date = $request['exam_date'];
        $te->exam_time = $request['exam_time'];
        $message = 'There was an error';
        if ($te->save()) {
            $message = 'kafa Exam successfully created';
        }
        return redirect()->route('kafa.exams')->with(['message' => $message]);
    }

    public function getDeletekafaExam($id)
    {
        $message = $warning = null;
        $post = kafaExam::where('id', $id)->first();
        try {
            $result = $post->delete();
            $message = "Module Successfully Deleted!";
        } catch (QueryException $e) {
            $warning = "kafa Exam was not Deleted, Try Again!";
        }
        return response()->json(['message' => $message, 'warning' => $warning]);
    }

    public function getLecturerkafaExams()
    {
        $lecturer = Auth::user();
        if (!$lecturer) {
            return redirect()->back()->with(['warning' => 'Lecturer data is not available!']);
        }
        $lecturer_id = $lecturer->profile_id;

        $employee = Employee::where('id', $lecturer_id)->first();
        if (!$employee) {
            return redirect()->back()->with(['warning' => 'Lecturer data is not available!']);
        }

        $kafaexams = kafaExam::select(
            'kafa_exams.number_students as number_students',
            'kafa_exams.number_pass as number_pass',
            'kafa_exams.exam_date as exam_date',
            'kafa_exams.exam_time as exam_time',
            'kafa_exams.exam_type as exam_type',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.module_id as module_id',
            'kafa_exams.id as id'
        )
            ->join("employee_module", function ($join) {
                $join->on("kafa_exams.module_id", "=", "employee_module.module_id")
                    ->on("kafa_exams.academic_year_id", "=", "employee_module.academic_year_id");
            })
            ->where('employee_id', $lecturer_id)
            ->orderBy('kafa_exams.academic_year_id', 'desc')
            ->orderBy('kafa_exams.module_id', 'asc')
            ->paginate(20);

        //return response()->json(['kafaexam' => $kafaexams, 'student' => $employee], 200);
        return view('examination.lecturer_kafa_exams', ['kafaexams' => $kafaexams, 'semesters' => $this->semesters, 'exams' => $this->exams]);

    }
}
