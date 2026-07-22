@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <p><strong>{{ $dateLabel }}:</strong> {{ $firing->jalali_date ?? $noText }}</p>
        <p><strong>{{ $productLabel }}:</strong> {{ $firing->product->name ?? $noText }}</p>
        <p><strong>{{ $inputLabel }}:</strong> {{ $firing->input_quantity }}</p>
        <p><strong>{{ $outputLabel }}:</strong> {{ $firing->output_quantity }}</p>
        <p><strong>{{ $packagedLabel }}:</strong> {{ $firing->is_packaged ? $yesText : $noText }}</p>
        <a href="{{ url('/tonneli') }}" class="btn btn-secondary">{{ $backText }}</a>
        <a href="{{ url('/tonneli/'.$firing->id.'/edit') }}" class="btn btn-warning ms-2">{{ $editText }}</a>
    </div>
</div>
@endsection