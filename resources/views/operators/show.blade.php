@extends('layouts.app')

@section('title', 'جزئیات اپراتور')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">{{ $operator->name }}</h4>
    <a href="{{ route('operators.index') }}" class="btn btn-secondary">بازگشت</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p><strong>نام:</strong> {{ $operator->name }}</p>
        <p><strong>وضعیت:</strong> {{ $operator->status ? 'فعال' : 'غیرفعال' }}</p>
    </div>
</div>
@endsection