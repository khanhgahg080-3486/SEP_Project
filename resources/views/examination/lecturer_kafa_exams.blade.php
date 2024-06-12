@extends('layouts.master')
@section('title')
    KAFA Exams Summary
@endsection
@section('content')
    <div class="card mb-3">
        <div class="card-header bg-white">
            <div class="align-items-center row">
                <div class="col">
                    <h5 class="mb-0 font-weight-bolder">KAFA Exams</h5>
                </div>
                <div class="col">

                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover  mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" class="pl-4">Module</th>
                            <th scope="col">Academic Year</th>
                            <th scope="col">Students</th>
                            <th scope="col">Pass Rate</th>
                            <th scope="col">Exam Date</th>
                            <th scope="col">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($kafaexams as $kafaexam)
                        <tr data-did="{{ $kafaexam->id }}">

                            <td class="pl-4" data-toggle="tooltip" data-placement="top"
                                title="{{ $kafaexam->module->course->name }}"><b>{{ $kafaexam->module->code }}</b>
                                {{ $kafaexam->module->name }} <small
                                    class="text-primary">{{ $exams[$kafaexam->exam_type] }}</small> </td>
                            <td><span data-toggle="tooltip" data-placement="top"
                                    title="{{ $kafaexam->academic_year->status }}"
                                    class="{{ $kafaexam->academic_year->status == 'Active' ? 'text-primary' : ($kafaexam->academic_year->status == 'Planning' ? 'text-dark' : 'text-secondary') }}"><i
                                        class="fas fa-check-circle"></i></span> {{ $kafaexam->academic_year->name }}</td>
                            <td>{{ $kafaexam->number_pass }} Pass of {{ $kafaexam->number_students }}
                            </td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                        style="width: {{ $kafaexam->number_students == 0 ? 0 : ($kafaexam->number_pass / $kafaexam->number_students) * 100 }}%"
                                        aria-valuenow="{{ $kafaexam->number_students == 0 ? 0 : ($kafaexam->number_pass / $kafaexam->number_students) * 100 }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                        {{ round($kafaexam->number_students == 0 ? 0 : ($kafaexam->number_pass / $kafaexam->number_students) * 100) }}%
                                    </div>
                                </div>
                            </td>
                            <td>{{ $kafaexam->exam_date }}</td>
                            <td>
                                <a class="btn btn-sm btn-light"
                                    href="{{ route('lecturer.kafa.exams.result', ['id' => $kafaexam->id]) }}"><i
                                        class="fas fa-eye"></i> Results</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="pt-1 no-gutters row">
                <div class="col">
                    <span>{{ $kafaexams->firstItem() }} to {{ $kafaexams->lastItem() }} of
                        {{ $kafaexams->total() }}</span>
                </div>
                <div class="col-auto">
                    {{ $kafaexams->links() }}
                </div>
            </div>
        </div>
    </div>
    <script>
        var token = '{{ Session::token() }}';
        var urlBatchesByCourse = '{{ route('ajax.batches') }}';
    </script>
@endsection
