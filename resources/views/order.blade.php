@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">

                    <a href="{{ route('run') }}?goods_id=1"> 开始抢 </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
