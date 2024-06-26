@extends('layouts.master')
@section('title')
    KAFA Exam Results Module
@endsection
@section('content')
    <div class="card mb-3">
        <div class="card-header bg-white">
            <div class="align-items-center row">
                <div class="col">
                    <h5 class="mb-0 font-weight-bolder">KAFA Examination Results by Module</h5>
                </div>
                <div class="text-right col-auto">
                    <a type="button" class="btn btn-sm btn-outline-primary shadow-sm" href="{{ url()->previous() }}">Back</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('kafa.exams.results.create') }}">
                <input type="hidden" value="{{ $kafaexam->id }}" name="kafa_exam_id">
                <div class="row align-items-center mt-2">
                    <div class="col-md-2">
                        <p class="font-weight-bold"> Course </p>
                    </div>
                    <div class="col-md-10">
                        <p class="font-weight-light"> {{ $kafaexam->module->course->name }} </p>
                    </div>

                    <div class="col-md-2">
                        <p class="font-weight-bold"> Module </p>
                    </div>
                    <div class="col-md-10">
                        <p class="font-weight-light"> {{ $kafaexam->module->name }} ({{ $exams[$kafaexam->exam_type] }})</p>
                    </div>
                    <div class="col-md-2">
                        <p class="font-weight-bold"> Semester </p>
                    </div>
                    <div class="col-md-10">
                        <p class="font-weight-light"> {{ $semesters[$kafaexam->module->semester_id] }}</p>
                    </div>
                    <div class="col-md-2">
                        <p class="font-weight-bold"> Examination Date </p>
                    </div>
                    <div class="col-md-10">
                        <p class="font-weight-light">
                            {{ Carbon\Carbon::parse($kafaexam->exam_date)->toFormattedDateString() }}
                            {{ Carbon\Carbon::parse($kafaexam->exam_time)->format('g:i A') }}</p>
                    </div>
                    <div class="col-md-2">
                        <p class="font-weight-bold"> Batch </p>
                    </div>
                    <div class="col-md-10">
                        <p class="font-weight-light"> {{ $batch->name }} ({{ $batch->academic_year->name }})</p>
                    </div>
                    <div class="col-md-12 mb-2">
                        <div class="form-inline">
                            <div class="col-md-3 offset-md-6">
                                @if (!count($students) > 0)
                                    <button type="button" class="btn btn-sm btn-primary" id="kafa_exam_results_add_batch"
                                        data-batch="{{ $batch->id }}"><i class="fas fa-plus-circle"></i> Add
                                        {{ $batch->name }} Students</button>
                                @endif
                            </div>
                            <div class="col-md-3 btn-group">
                                <input type="text" placeholder="2020/ICT/5IT01" class="form-control form-control-sm"
                                    id="kafa_exam_results_name_repeat">
                                <button type="button" class="btn btn-sm btn-primary" id="kafa_exam_results_add_repeat"><i
                                        class="fas fa-plus-circle"></i> Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 table-responsive">
                        <table class="table table-hover table-borderless">
                            <thead>
                                <tr class="bg-primary text-light">
                                    <th scope="col">Student ID</th>
                                    <th scope="col">Student Name</th>
                                    <th scope="col">Result</th>
                                    <th scope="col">Attempt</th>
                                </tr>
                            </thead>
                            <tbody id="kafa_exam_results">
                                @if (count($students) > 0)
                                    <span hidden> {{ $student_id = 0 }}}</span>
                                    @foreach ($students as $student)
                                        @if ($student_id != $student->id)
                                            <span hidden>{{ $student_id = $student->id }}</span>
                                            <tr>
                                                <td>{{ $student->reg_no }}</td>
                                                <td>{{ $student->shortname }}</td>
                                                <td>
                                                    <select class="custom-select custom-select-sm"
                                                        name="results[{{ $student->id }}]" required>
                                                        <option value="" selected>Select</option>
                                                        <option value="P"
                                                            {{ $student->result == 'P' ? 'selected' : '' }}>Pass</option>
                                                        <option value="F"
                                                            {{ $student->result == 'F' ? 'selected' : '' }}>Fail</option>
                                                        <option value="AB"
                                                            {{ $student->result == 'AB' ? 'selected' : '' }}>Absent
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="custom-select custom-select-sm"
                                                        name="attempts[{{ $student->id }}]" required>
                                                        <option value="1"
                                                            {{ $student->attempt == 1 ? 'selected' : '' }}>Attempt 1
                                                        </option>
                                                        <option value="2"
                                                            {{ $student->attempt == 2 ? 'selected' : '' }}>Attempt 2
                                                        </option>
                                                        <option value="3"
                                                            {{ $student->attempt == 3 ? 'selected' : '' }}>Attempt 3
                                                        </option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-sm btn-primary float-right"><i class="fas fa-save"></i>
                                Save</button>
                            <input type="hidden" name="_token" value="{{ Session::token() }}">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        var token = '{{ Session::token() }}';
        var urlStudentsByBatch = '{{ route('ajax.students.batch') }}';
        var urlStudentByReg = '{{ route('ajax.students.reg') }}';
    </script>
@endsection
