@extends('layouts.app')

@section('title', 'جزئیات پرس')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">{{ $press->name }}</h4>
    <a href="{{ route('presses.index') }}" class="btn btn-secondary">بازگشت</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p><strong>نام:</strong> {{ $press->name }}</p>
        <p><strong>وضعیت:</strong> {{ $press->status ? 'فعال' : 'غیرفعال' }}</p>
    </div>
</div>
@endsection