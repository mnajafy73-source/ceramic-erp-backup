@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'موردی یافت نشد'; }
            }
        });
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">کارتن و لایه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>نوع</th>
                        <th>نام</th>
                        <th>موجودی (عدد)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packagings as $packaging)
                    <tr>
                        <td>{{ $packaging->type == 'carton' ? 'کارتن' : 'لایه' }}</td>
                        <td>{{ $packaging->name }}</td>
                        <td>{{ number_format($packaging->stock) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">هیچ کارتن یا لایه‌ای تعریف نشده است.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection