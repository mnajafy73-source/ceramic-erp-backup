@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
    .table-sm td, .table-sm th {
        font-size: 0.85rem;
        padding: 0.3rem 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#product-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'جستجوی محصول...',
            allowClear: true,
            language: 'fa'
        });
    });
</script>
@endpush

@section('title', 'داشبورد')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0 fw-bold small">خوراک پخت کوره تونلی</h6>
                <div>
                    <form action="{{ route('dashboard.add-product') }}" method="POST" class="d-inline">
                        @csrf
                        <select name="product_id" id="product-select" class="form-select form-select-sm d-inline" style="display:inline-block; width:200px;">
                            <option value="">افزودن محصول...</option>
                            @foreach($allProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary py-0 px-2">➕</button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                @if(count($feedRateData) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">نام محصول</th>
                                    <th class="small text-center">موجودی خام</th>
                                    <th class="small text-center">ساعت موجودی</th>
                                    <th class="small text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($feedRateData as $item)
                                <tr>
                                    <td class="small">{{ $item['name'] }}</td>
                                    <td class="small text-center">{{ number_format($item['raw_stock']) }}</td>
                                    <td class="small text-center">{{ number_format($item['hours'], 2) }}</td>
                                    <td class="small text-center">
                                        <form action="{{ route('dashboard.remove-product') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="حذف از لیست">✖</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 text-center text-muted small">
                        <i class="fas fa-info-circle me-1"></i> هیچ محصولی انتخاب نشده است. از منوی بالا محصول اضافه کنید.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection