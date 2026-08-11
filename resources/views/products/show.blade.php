@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات کالا</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">کالاها</a></li>
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>کد کالا</th><td>{{ $product->code }}</td></tr>
                    <tr><th>نام کالا</th><td>{{ $product->name }}</td></tr>
                    <tr><th>واحد</th><td>{{ $product->unit->name ?? '-' }}</td></tr>
                    <tr><th>وزن (گرم)</th><td>{{ $product->weight ?? '-' }}</td></tr>
                    <tr><th>فرمول</th><td>{{ $product->formula->name ?? '-' }}</td></tr>
                    <tr><th>تعداد حفره قالب</th><td>{{ $product->cavities ?? '-' }}</td></tr>
                    <tr><th>خوراک پخت کوره تونلی</th><td>{{ $product->tonneli_feed_rate ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>تعداد در کارتن</th><td>{{ $product->per_box ?? '-' }}</td></tr>
                    <tr><th>تعداد در بسته</th><td>{{ $product->per_pack ?? '-' }}</td></tr>
                    <tr><th>تعداد در پالت</th><td>{{ $product->per_pallet ?? '-' }}</td></tr>
                    <tr><th>تعداد لایه در کارتن</th><td>{{ $product->layers_per_box ?? '-' }}</td></tr>
                    <tr><th>کارتن مصرفی</th><td>{{ $product->cartonPackaging->name ?? '-' }}</td></tr>
                    <tr><th>لایه مصرفی</th><td>{{ $product->layerPackaging->name ?? '-' }}</td></tr>
                    <tr><th>وضعیت</th><td>{{ $product->status ? 'فعال' : 'غیرفعال' }}</td></tr>
                    <tr><th>در حال تولید</th><td>{{ $product->in_production ? 'بله' : 'خیر' }}</td></tr>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">بازگشت</a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">ویرایش</a>
        </div>
    </div>
</div>
@endsection