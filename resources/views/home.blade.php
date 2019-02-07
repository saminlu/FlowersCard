@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    <a href="{{ route('order') }}?goods_id=1"> <img src="images/rob.jpg"> </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
