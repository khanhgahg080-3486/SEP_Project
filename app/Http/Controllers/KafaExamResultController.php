<?php

namespace App\Http\Controllers;

use App\Batch;
use App\Course;
use App\Module;
use App\Student;
use App\StudentEnroll;
use App\kafaExam;
use App\kafaExamResult;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class kafaExamResultController extends Controller
{
    private $exam_types = array('T' => 'Theory', 'P' => 'Practical', 'B' => 'Theory and Practical');
    private $exam_pass = array('P' => 'Pass', 'F' => 'Fail', 'AB' => 'Absent');
    private $semesters = array('1' => 'Semester 1', '2' => 'Semester 2');
    private $exams = array('T' => 'Theory', 'P' => 'Practical');
    public function postkafaExamsResultsCreate(Request $request)
    {
        $data = array();
        $i = 0;
        $kafa_exam = kafaExam::where('id', $request['kafa_exam_id'])->first();
        $number_of_students = 0;
        $number_of_students_pass = 0;

        foreach ($request['results'] as $key => $value) {

            $isUpdate = false;
            $exams = kafaExamResult::where([['student_id', $key], ['attempt', $request['attempts'][$key]], ['kafa_exam_id', $request['kafa_exam_id']]])->count();
            if ($exams == 1) {
                $isUpdate = true;
                $result = kafaExamResult::where([['student_id', $key], ['attempt', $request['attempts'][$key]], ['kafa_exam_id', $request['kafa_exam_id']]])->first();

            } else {
                $result = new kafaExamResult();
            }

            if ($request['results'][$key] == 'P' && $result->result != 'P') {
                $number_of_students_pass++;
            }
            //Sit any previews exam on this module
            $isSiteExam = kafaExamResult::leftjoin('kafa_exams', 'kafa_exams.id', '=', 'kafa_exam_results.kafa_exam_id')
                ->where([['student_id', $key], ['module_id', $kafa_exam->module_id], ['exam_type', $kafa_exam->exam_type]])
                ->first();
            if ($isSiteExam) {
                $academic_year_id = $isSiteExam->academic_year_id;
                $kafa_exam_id = $isSiteExam->kafa_exam_id;
            } else {
                $academic_year_id = $kafa_exam->academic_year_id;
                $kafa_exam_id = $request['kafa_exam_id'];
            }
            //Update Student Enroll Results
            $student_enroll = StudentEnroll::where([['student_id', $key], ['academic_year_id', $academic_year_id]])->first();
            $count = kafaExamResult::where([['student_id', $key], ['kafa_exam_id', $kafa_exam_id]])->count();
            if ($count == 0) {
                $student_enroll->kafa_exam_modules += 1;
            }
            if ($request['results'][$key] == 'P' && $result->result != 'P')
                $student_enroll->kafa_exam_pass += 1;
            $student_enroll->update();


            $result->student_id = $key;
            $result->attempt = $request['attempts'][$key];
            $result->result = $request['results'][$key];
            $result->kafa_exam_id = $request['kafa_exam_id'];
            if (!$isUpdate) {
                $count = kafaExamResult::where([['student_id', $key], ['kafa_exam_id', $request['kafa_exam_id']]])->count();
                if ($count < 1) {
                    $number_of_students++;
                }
                $result->save();
                $message = "Exam Results Successfully Created";
            } else {
                $result->update();
                $message = "Exam Results Successfully Updated";
            }
            $i++;
        }

        $kafa_exam->number_students += $number_of_students;
        $kafa_exam->number_pass += $number_of_students_pass;
        $kafa_exam->update();
        //return response()->json(['result' => $kafa_exam, 'student_enroll' => $isSiteExam,], 200);
        return redirect()->back()->with(['message' => $message]);
    }

    public function getkafaExamsResultsbyBatch($id)
    {
        $batch = Batch::where('id', $id)->first();
        if (!$batch) {
            return redirect()->route('batches');
        }
        $students = Student::select('students.id as id', 'students.shortname', 'students.reg_no', 'student_enrolls.kafa_exam_pass', 'student_enrolls.kafa_exam_modules')->leftjoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('student_id', 'asc')
            ->get();
        $exams = kafaExam::select(
            'modules.code as module_code',
            'modules.name as module_name',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.exam_type as exam_type',
            'courses.code as course_code',
            'kafa_exams.exam_date as exam_date'
        )
            ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
            ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('module_id', 'asc')
            ->orderBy('exam_type', 'desc')
            ->get();
        $results = [];
        foreach ($students as $student) {
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
                ->where([['student_id', $student->id], ['course_id', $batch->course_id]])
                ->get();
            $results[] = $result;
        }
        //return response()->json(['students' => $students, 'results' => $results, 'exams' => $exams,], 200);
        return view('examination.kafa_batch_results', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass]);


    }
    public function getkafaExamsResultsbyBatchPass($id)
    {
        $batch = Batch::where('id', $id)->first();
        if (!$batch) {
            return redirect()->route('batches');
        }
        $students = Student::select('students.id as id', 'students.shortname', 'students.reg_no', 'student_enrolls.kafa_exam_pass', 'student_enrolls.kafa_exam_modules')
            ->leftjoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id], ['student_enrolls.kafa_exam_modules', '=', DB::raw('student_enrolls.kafa_exam_pass')]])
            ->orderBy('student_id', 'asc')
            ->get();
        $exams = kafaExam::select(
            'modules.code as module_code',
            'modules.name as module_name',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.exam_type as exam_type',
            'courses.code as course_code',
            'kafa_exams.exam_date as exam_date'
        )
            ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
            ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('module_id', 'asc')
            ->orderBy('exam_type', 'desc')
            ->get();
        $results = [];
        foreach ($students as $student) {
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
                ->where([['student_id', $student->id], ['course_id', $batch->course_id]])
                ->get();
            $results[] = $result;
        }
        //return response()->json(['students' => $students, 'results' => $results, 'exams' => $exams,], 200);
        return view('examination.kafa_batch_results', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass]);


    }
    public function getkafaExamsResultsbyBatchPassPDF($id)
    {
        $batch = Batch::where('id', $id)->first();
        if (!$batch) {
            return redirect()->route('batches');
        }
        $students = Student::select('students.id as id', 'students.shortname', 'students.reg_no', 'student_enrolls.kafa_exam_pass', 'student_enrolls.kafa_exam_modules')->leftjoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id], ['student_enrolls.kafa_exam_modules', '=', DB::raw('student_enrolls.kafa_exam_pass')]])
            ->orderBy('student_id', 'asc')
            ->get();
        $exams = kafaExam::select(
            'modules.code as module_code',
            'modules.name as module_name',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.exam_type as exam_type',
            'courses.code as course_code',
            'kafa_exams.exam_date as exam_date'
        )
            ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
            ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('module_id', 'asc')
            ->orderBy('exam_type', 'desc')
            ->get();
        $results = [];
        foreach ($students as $student) {
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
                ->where([['student_id', $student->id], ['course_id', $batch->course_id]])
                ->get();
            $results[] = $result;
        }

        $data = ['title' => 'Academic Transcript (Passed Students List)' . $batch->name];
        $pdf = PDF::loadView('examination.kafa_batch_results_pdf', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass])->setPaper('a4', 'landscape');
        return $pdf->download('kafa-Transcript-' . $batch->name . "-" . $batch->course_id . '.pdf');
        //return response()->json(['students' => $students, 'results' => $results, 'exams' => $exams,], 200);
        //return view('examination.kafa_batch_results_pdf', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass]);

    }
    public function getkafaExamsResultsbyBatchPDF($id)
    {
        $batch = Batch::where('id', $id)->first();
        if (!$batch) {
            return redirect()->route('batches');
        }
        $students = Student::select('students.id as id', 'students.shortname', 'students.reg_no', 'student_enrolls.kafa_exam_pass', 'student_enrolls.kafa_exam_modules')->leftjoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('student_id', 'asc')
            ->get();
        $exams = kafaExam::select(
            'modules.code as module_code',
            'modules.name as module_name',
            'kafa_exams.academic_year_id as academic_year_id',
            'kafa_exams.exam_type as exam_type',
            'courses.code as course_code',
            'kafa_exams.exam_date as exam_date'
        )
            ->leftJoin('modules', 'modules.id', '=', 'kafa_exams.module_id')
            ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
            ->where([['academic_year_id', $batch->academic_year_id], ['course_id', $batch->course_id]])
            ->orderBy('module_id', 'asc')
            ->orderBy('exam_type', 'desc')
            ->get();
        $results = [];
        foreach ($students as $student) {
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
                ->where([['student_id', $student->id], ['course_id', $batch->course_id]])
                ->get();
            $results[] = $result;
        }

        $data = ['title' => 'Academic Transcript' . $batch->name];
        $pdf = PDF::loadView('examination.kafa_batch_results_pdf', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass])->setPaper('a4', 'landscape');
        return $pdf->download('kafa-Transcript-' . $batch->name . "-" . $batch->course_id . '.pdf');
        //return response()->json(['students' => $students, 'results' => $results, 'exams' => $exams,], 200);
        //return view('examination.kafa_batch_results_pdf', ['students' => $students, 'exams' => $exams, 'results' => $results, 'exam_types' => $this->exam_types, 'batch' => $batch, 'exam_pass' => $this->exam_pass]);

    }

    public function getkafaExamsResultsbyStudentId($bid, $id)
    {
        $batch = Batch::where('id', $bid)->first();
        $student = Student::where('id', $id)->first();
        if (!$student) {
            return redirect()->route('students');
        }
        if (!$batch) {
            return redirect()->route('batches');
        }
        $results = kafaExamResult::select(
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
            ->where([['student_id', $student->id], ['course_id', $batch->course_id]])
            ->get();
        //return response()->json(['results'=>$results,'student'=>$student],200);
        return view('examination.kafa_student_results', ['results' => $results, 'exam_types' => $this->exam_types, 'student' => $student, 'batch' => $batch, 'exam_pass' => $this->exam_pass]);
    }

    public function getkafaResults()
    {
        $courses = Course::orderBy('name', 'asc')->get();
        return view('examination.kafa_results', ['courses' => $courses]);
    }

    public function postkafaExamsResultsbyBatch(Request $request)
    {
        $this->validate($request, ['batch_id' => 'required']);
        $id = $request['batch_id'];
        return redirect()->route('kafa.exams.results.batch', ['id' => $id]);
    }

    public function getStudentExamsIndex()
    {
        $student_id = Auth::user()->profile_id;

        if (!$student_id) {
            return redirect()->back()->with(['warning' => 'Student ID is invalid. Try again!']);
        }

        $enrolls = StudentEnroll::where('student_id', $student_id)->get();

        $results = [];
        $batches = [];
        foreach ($enrolls as $enroll) {
            $batch = Batch::where([['course_id', $enroll->course_id], ['academic_year_id', $enroll->academic_year_id]])->first();
            if (!$batch) {
                return redirect()->back()->with(['warning' => 'Invalid Batch Name. Try again.']);
            }
            $batches[] = $batch;
            $result = kafaExamResult::select(
                'module_id',
                'kafa_exams.exam_type',
                DB::raw('max(kafa_exam_results.attempt) as attempt'),
                DB::raw('max(kafa_exam_results.result) as result'),
                DB::raw('max(modules.name) as module_name'),
                DB::raw('max(modules.code) as module_code'),
                DB::raw('max(academic_years.id) as academic_year_id')
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
        //return response()->json(['results'=>$results],200);
        return view('examination.student_kafa_results', ['results' => $results, 'exam_types' => $this->exam_types, 'exam_results' => $this->exam_pass, 'batches' => $batches]);

    }
    public function getLecturerkafaExamsResult($id)
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
        return view('examination.kafa_exam_results_view', ['students' => $students, 'kafaexam' => $kafaexam, 'exam_pass' => $this->exam_pass, 'semesters' => $this->semesters, 'exams' => $this->exams, 'batch' => $batch]);

    }
}
