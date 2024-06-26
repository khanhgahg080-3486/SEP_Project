@extends('layouts.master')
@section('title')
    KAFA Examination Results
@endsection
@section('content')
    <div class="card mb-3">
        <div class="card-header bg-white border-0">
            <div class="align-items-center row">
                <div class="col">
                    <h5 class="mb-0 font-weight-bolder">KAFA Examination Results </h5>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div class="card-body">

            @foreach ($results as $index => $rows)
                <div class="row  border-bottom bg-light pt-3">
                    <div class="col-md-2">
                        <p class="font-weight-bold">Course</p>
                    </div>
                    <div class="col-md-6">
                        <p class="font-weight-light">{{ $batches[$index]->course->name }}</p>
                    </div>
                    <div class="col-md-1">
                        <p class="font-weight-bold">Batch</p>
                    </div>
                    <div class="col-md-3">
                        <p class="font-weight-light">{{ $batches[$index]->name }}
                            ({{ $batches[$index]->academic_year->name }})
                        </p>
                    </div>
                </div>
                <div class="row mt-2">
                    @foreach ($rows as $result)
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header  border-0">
                                    <div class="align-items-center row">
                                        <div class="col">
                                            <p class="mb-0  font-weight-lighter"> {{ $result->module_code }}
                                                {{ $result->module_name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body ">
                                    <div class="align-items-center row">
                                        <div class="col">
                                            <div
                                                class="h1 font-weight-lighter {{ $result->result != 'P' ? 'text-danger' : 'text-primary' }}">
                                                {{ $exam_results[$result->result] }}</div>

                                        </div>
                                        <div class="col-auto text-right">
                                            <div>{{ $exam_types[$result->exam_type] }}</div>
                                            <div>Attempt <span class="text-muted">{{ $result->attempt }}</span></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

        </div>
        <div class="card-footer border-0">
            <div class="row ">
                <div class="col">

                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
    </div>
@endsection
