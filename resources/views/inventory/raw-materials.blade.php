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
        $('.material-search-select').select2({
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
    <h4 class="fw-bold mb-1">موجودی مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">مواد اولیه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('inventory.raw-materials') }}" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select material-search-select" style="width: 100%;">
                        <option value="">همه مواد...</option>
                        @foreach(\App\Models\RawMaterial::orderBy('name')->get() as $material)
                            <option value="{{ $material->id }}" {{ request('search') == $material->id ? 'selected' : '' }}>
                                {{ $material->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    @if(request('search'))
                        <a href="{{ route('inventory.raw-materials') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>نام ماده</th>
                        <th>واحد</th>
                        <th>موجودی (کیلوگرم)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @if(!request('search') || request('search') == $material->id)
                        <tr>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->unit == 'kg' ? 'کیلوگرم' : 'تن' }}</td>
                            <td>{{ number_format($material->stock, 2) }}</td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">
                            @if(request('search'))
                                ماده‌ای با این شناسه یافت نشد.
                            @else
                                هیچ ماده اولیه‌ای ثبت نشده است.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection