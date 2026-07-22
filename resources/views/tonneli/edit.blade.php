@extends('layouts.app')

@push('scripts')
<script>
    $(function() {
        $('#date').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false,
            observer: true,
            calendar: { persian: { locale: 'fa' } }
        });
    });
</script>
@endpush

@section('content')
<div class="card">
    <div class="card-body">
        <h4>{{ $updateText }}</h4>
        <form action="{{ url('/tonneli/'.$firing->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>{{ $dateLabel }} <span class="text-danger">*</span></label>
                <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                       value="{{ old('date', $firing->jalali_date) }}" required autocomplete="off">
                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label>{{ $productLabel }} <span class="text-danger">*</span></label>
                <select name="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                    <option value="">{{ $selectText }}</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id', $firing->product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label>{{ $inputLabel }}</label>
                <input type="number" name="input_quantity" class="form-control" value="{{ old('input_quantity', $firing->input_quantity) }}" step="0.01">
            </div>
            <div class="mb-3">
                <label>{{ $outputLabel }}</label>
                <input type="number" name="output_quantity" class="form-control" value="{{ old('output_quantity', $firing->output_quantity) }}" step="0.01">
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_packaged" id="is_packaged" value="1" 
                           {{ old('is_packaged', $firing->is_packaged) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_packaged">{{ $packagedLabel }}</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ $updateText }}</button>
            <a href="{{ url('/tonneli') }}" class="btn btn-secondary ms-2">{{ $cancelText }}</a>
        </form>
    </div>
</div>
@endsection