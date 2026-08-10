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
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() {
                    return 'در حال جستجو...';
                },
                noResults: function() {
                    return 'محصولی یافت نشد';
                }
            }
        });
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی انبار</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">موجودی انبار</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <!-- فرم جستجو -->
        <form action="{{ route('inventory.warehouse') }}" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select product-search-select" style="width: 100%;">
                        <option value="">همه محصولات...</option>
                        @foreach(\App\Models\Product::where('status', 1)->orderBy('name')->get() as $product)
                            <option value="{{ $product->id }}" {{ request('search') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    @if(request('search'))
                        <a href="{{ route('inventory.warehouse') }}" class="btn btn-secondary">
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
                        <th>نام محصول</th>
                        <th>موجودی انبار (عدد)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventories as $item)
                    <tr>
                        <td>{{ $item['product']->name }}</td>
                        <td>{{ number_format($item['stock']) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center">
                            @if(request('search'))
                                محصولی با این شناسه یافت نشد.
                            @else
                                هیچ محصولی یافت نشد.
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