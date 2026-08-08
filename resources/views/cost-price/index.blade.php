@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">قیمت تمام شده</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">قیمت تمام شده</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">آخرین قیمت هر ماده اولیه</h5>
            </div>
            <div class="card-body">
                @if(count($lastRawMaterialPurchases) > 0)
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ماده</th>
                                <th>آخرین قیمت هر گرم (ریال)</th>
                                <th>تاریخ خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastRawMaterialPurchases as $item)
                            <tr>
                                <td>{{ $item->raw_material->name }}</td>
                                <td>{{ number_format($item->price_per_gram, 2) }}</td>
                                <td>{{ jdate($item->purchase_date)->format('Y/m/d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">هیچ خریدی ثبت نشده است.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">آخرین قیمت هر کارتن/لایه</h5>
            </div>
            <div class="card-body">
                @if(count($lastPackagingPurchases) > 0)
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>نام</th>
                                <th>آخرین قیمت هر عدد (ریال)</th>
                                <th>تاریخ خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastPackagingPurchases as $item)
                            <tr>
                                <td>{{ $item->packaging->name }}</td>
                                <td>{{ number_format($item->price_per_unit, 2) }}</td>
                                <td>{{ jdate($item->purchase_date)->format('Y/m/d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">هیچ خریدی ثبت نشده است.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection