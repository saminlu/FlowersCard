@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if($tips == "抢花花卡成功")
                        {{$tips}}
                        <img src="images/timg.jpg">
                    @else
                        {{$tips}}
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
