@extends('layouts.master')
@section('title')
Add a Academic Year
@endsection
@section('content')
<div class="card mb-3">
    <div class="card-header bg-white">
        <div class="align-items-center row">
            <div class="col">
                <h5 class="mb-0 font-weight-bolder">Add a Academic Year</h5>
            </div>
            <div class="text-right col-auto">
                <a type="button" class="btn btn-sm btn-outline-primary shadow-sm" href="{{route('academics')}}">Back</a>
            </div>
        </div>
    </div>
    <div class="card-body">
       <form method="post" action="{{route('academics.create')}}">
            <div class="row align-items-center mt-2">
                <div class="form-group col-md-6">
                    <label for="name">Academic Year Name</label>
                    <input id="name" class="form-control" type="text" name="name" placeholder="2016/2017" pattern="[0-9]{4}/[0-9]{4}" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="status">Status</label>
                    <select class="custom-select d-block w-100" id="status" name="status" required>
                        <option disabled selected >Select Status</option>
                        @foreach($status as $id => $name) 
                        <option value ="{{$name}}" >{{$name}}</option> 
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="start">Start Date</label>
                    <input id="start" class="form-control" type="date" name="start" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="end">End Date</label>
                    <input id="end" class="form-control" type="date" name="end" required>
                </div>
                <div class="form-group col-md-12">
                <button type="submit" class="btn btn-sm btn-primary float-right" >Save</button>
                <input type="hidden" name="_token" value="{{Session::token()}}">
                </div>
            </div>
        </form>
    </div>
</div>
@endsection