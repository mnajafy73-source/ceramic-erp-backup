<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Formula;
use App\Models\Packaging;
use App\Models\ProductAlias;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ============================================================
    //  نمایش لیست محصولات (با فیلتر دسته‌بندی)
    // ============================================================
    public function index(Request $request)
    {
        session(['products_return_url' => $request->fullUrl()]);

        $query = Product::with('unit', 'parent', 'children');

        // ✅ فیلتر دسته‌بندی
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('product_type', $category);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sortField = in_array($request->input('sort'), ['code', 'name', 'status'])
                    ? $request->input('sort') : 'code';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $products = $query->orderBy($sortField, $sortDirection)
                          ->paginate(10)
                          ->appends($request->all());

        // ✅ داده‌های موردنیاز برای ویرایش سریع
        $packagings = Packaging::all();
        $formulas   = Formula::orderBy('name')->get();

        $productsJson = [];
        foreach ($products as $p) {
            $productsJson[$p->id] = [
                'id' => $p->id,
                'name' => $p->name,
                'unit_id' => $p->unit_id,
                'product_type' => $p->product_type ?? 'normal',
                'weight' => $p->weight,
                'formula_id' => $p->formula_id,
                'carton_packaging_id' => $p->carton_packaging_id,
                'layer_packaging_id' => $p->layer_packaging_id,
                'per_box' => $p->per_box,
                'layers_per_box' => $p->layers_per_box,
                'per_pack' => $p->per_pack,
                'per_pallet' => $p->per_pallet,
                'tonneli_feed_rate' => $p->tonneli_feed_rate,
            ];
        }

        return view('products.index', compact('products', 'packagings', 'formulas', 'productsJson'));
    }

    // ============================================================
    //  فرم ایجاد محصول
    // ============================================================
    public function create()
    {
        $units = Unit::all();
        $formulas = Formula::all();
        $packagings = Packaging::all();
        $allProducts = Product::orderBy('name')->get();

        $defaultUnitId = Unit::where('name', 'عدد')->value('id')
                        ?? Unit::first()->id
                        ?? null;

        return view('products.create', compact(
            'units', 'formulas', 'packagings', 'allProducts', 'defaultUnitId'
        ));
    }

    // ============================================================
    //  ذخیره محصول جدید
    // ============================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:products,name',
            'unit_id'       => 'required|exists:units,id',
            'tonneli_feed_rate' => 'nullable|integer|min:0',
            'per_box'       => 'nullable|integer|min:0',
            'per_pack'      => 'nullable|integer|min:0',
            'per_pallet'    => 'nullable|integer|min:0',
            'layers_per_box'=> 'nullable|integer|min:0',
            'weight'        => 'nullable|numeric|min:0',
            'formula_id'    => 'nullable|exists:formulas,id',
            'carton_packaging_id' => 'nullable|exists:packagings,id',
            'layer_packaging_id'  => 'nullable|exists:packagings,id',
            'parent_product_id' => 'nullable|exists:products,id',
            'product_type'  => 'nullable|in:normal,rod,pipe,injection',
        ]);

        if (empty($validated['product_type'])) {
            $validated['product_type'] = 'normal';
        }

        $validated['code'] = $this->generateUniqueCode();
        $validated['status'] = 1;
        $validated['firing_process'] = 'both';

        $validated['tonneli_feed_rate'] = $validated['tonneli_feed_rate'] ?? null;
        $validated['per_box'] = $validated['per_box'] ?? null;
        $validated['per_pack'] = $validated['per_pack'] ?? null;
        $validated['per_pallet'] = $validated['per_pallet'] ?? null;
        $validated['layers_per_box'] = $validated['layers_per_box'] ?? null;
        $validated['weight'] = $validated['weight'] ?? null;
        $validated['formula_id'] = $validated['formula_id'] ?? null;

        $product = Product::create($validated);

        if ($request->has('aliases')) {
            $aliases = array_filter(array_map('trim', explode(',', $request->aliases)));
            foreach ($aliases as $alias) {
                if (!empty($alias)) {
                    ProductAlias::create([
                        'product_id' => $product->id,
                        'alias' => $alias,
                    ]);
                }
            }
        }

        $returnUrl = session('products_return_url', route('products.index'));
        return redirect($returnUrl)->with('success', 'کالا با موفقیت ایجاد شد.');
    }

    // ============================================================
    //  نمایش محصول
    // ============================================================
    public function show(Product $product)
    {
        $product->load('unit', 'logs.user', 'parent', 'children', 'aliases');
        return view('products.show', compact('product'));
    }

    // ============================================================
    //  فرم ویرایش محصول
    // ============================================================
    public function edit(Product $product)
    {
        $units = Unit::all();
        $formulas = Formula::all();
        $packagings = Packaging::all();
        $allProducts = Product::where('id', '!=', $product->id)->orderBy('name')->get();
        $product->load('aliases', 'parent', 'children');
        return view('products.edit', compact('product', 'units', 'formulas', 'packagings', 'allProducts'));
    }

    // ============================================================
    //  ذخیره ویرایش محصول
    // ============================================================
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:products,name,' . $product->id,
            'unit_id'       => 'required|exists:units,id',
            'tonneli_feed_rate' => 'nullable|integer|min:0',
            'per_box'       => 'nullable|integer|min:0',
            'per_pack'      => 'nullable|integer|min:0',
            'per_pallet'    => 'nullable|integer|min:0',
            'layers_per_box'=> 'nullable|integer|min:0',
            'weight'        => 'nullable|numeric|min:0',
            'formula_id'    => 'nullable|exists:formulas,id',
            'carton_packaging_id' => 'nullable|exists:packagings,id',
            'layer_packaging_id'  => 'nullable|exists:packagings,id',
            'parent_product_id' => 'nullable|exists:products,id',
            'product_type'  => 'nullable|in:normal,rod,pipe,injection',
        ]);

        if (empty($validated['product_type'])) {
            $validated['product_type'] = $product->product_type ?? 'normal';
        }

        unset($validated['code']);

        $validated['weight'] = $validated['weight'] ?? null;
        $validated['formula_id'] = $validated['formula_id'] ?? null;

        $product->update($validated);

        if ($request->has('aliases')) {
            $product->aliases()->delete();
            $aliases = array_filter(array_map('trim', explode(',', $request->aliases)));
            foreach ($aliases as $alias) {
                if (!empty($alias)) {
                    ProductAlias::create([
                        'product_id' => $product->id,
                        'alias' => $alias,
                    ]);
                }
            }
        }

        $returnUrl = session('products_return_url', route('products.index'));
        return redirect($returnUrl)->with('success', 'کالا با موفقیت ویرایش شد.');
    }

    // ============================================================
    //  ✅ ویرایش سریع (AJAX از مدال)
    // ============================================================
    public function quickUpdate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:products,name,' . $product->id,
            'unit_id'       => 'required|exists:units,id',
            'product_type'  => 'required|in:normal,rod,pipe,injection',
            'weight'        => 'nullable|numeric|min:0',
            'per_box'       => 'nullable|integer|min:0',
            'per_pack'      => 'nullable|integer|min:0',
            'per_pallet'    => 'nullable|integer|min:0',
            'layers_per_box'=> 'nullable|integer|min:0',
            'tonneli_feed_rate' => 'nullable|integer|min:0',
            'formula_id'    => 'nullable|exists:formulas,id',
            'carton_packaging_id' => 'nullable|exists:packagings,id',
            'layer_packaging_id'  => 'nullable|exists:packagings,id',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'کالا با موفقیت ویرایش شد.',
            'product' => [
                'id'           => $product->id,
                'name'         => $product->name,
                'code'         => $product->code,
                'product_type' => $product->product_type,
                'type_label'   => $product->product_type_label,
                'type_badge'   => $product->product_type_badge,
                'status'       => (bool) $product->status,
            ],
        ]);
    }

    // ============================================================
    //  ✅ ویرایش درجا (Inline)
    // ============================================================
    public function inlineUpdate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'field' => 'required|in:carton_packaging_id,per_box,layer_packaging_id,layers_per_box,product_type,tonneli_feed_rate,formula_id',
            'value' => 'nullable',
        ]);

        $field = $validated['field'];
        $value = $validated['value'];

        switch ($field) {
            case 'carton_packaging_id':
            case 'layer_packaging_id':
                if ($value !== null && $value !== '') {
                    if (!Packaging::where('id', $value)->exists()) {
                        return response()->json(['success' => false, 'error' => 'مقدار نامعتبر.'], 422);
                    }
                    $value = (int) $value;
                } else {
                    $value = null;
                }
                break;

            case 'formula_id':
                if ($value !== null && $value !== '') {
                    if (!Formula::where('id', $value)->exists()) {
                        return response()->json(['success' => false, 'error' => 'فرمول نامعتبر.'], 422);
                    }
                    $value = (int) $value;
                } else {
                    $value = null;
                }
                break;

            case 'per_box':
            case 'layers_per_box':
            case 'tonneli_feed_rate':
                if ($value !== null && $value !== '') {
                    if (!is_numeric($value) || (int) $value < 0) {
                        return response()->json(['success' => false, 'error' => 'عدد نامعتبر.'], 422);
                    }
                    $value = (int) $value;
                } else {
                    $value = null;
                }
                break;

            case 'product_type':
                if (!in_array($value, ['normal', 'rod', 'pipe', 'injection'])) {
                    return response()->json(['success' => false, 'error' => 'دسته‌بندی نامعتبر.'], 422);
                }
                break;
        }

        $product->{$field} = $value;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'ذخیره شد.',
            'field' => $field,
            'value' => $product->{$field},
            'product_type_label' => $product->product_type_label,
            'product_type_badge' => $product->product_type_badge,
        ]);
    }

    // ============================================================
    //  تغییر وضعیت (AJAX)
    // ============================================================
    public function toggleStatus(Product $product)
    {
        $product->status = !$product->status;
        $product->save();
        return response()->json(['success' => true, 'status' => $product->status]);
    }

    // ============================================================
    //  حذف محصول
    // ============================================================
    public function destroy(Product $product)
    {
        try {
            $product->aliases()->delete();
            $product->delete();

            $returnUrl = session('products_return_url', route('products.index'));
            return redirect($returnUrl)->with('success', 'کالا حذف شد.');

        } catch (\Exception $e) {
            $returnUrl = session('products_return_url', route('products.index'));
            return redirect($returnUrl)->with('error', $e->getMessage());
        }
    }

    // ============================================================
    //  ✅ ساخت کد یکتا
    // ============================================================
    private function generateUniqueCode(): string
    {
        $maxId = (int) (Product::max('id') ?? 0) + 1;
        $code = 'P' . str_pad($maxId, 5, '0', STR_PAD_LEFT);

        while (Product::where('code', $code)->exists()) {
            $maxId++;
            $code = 'P' . str_pad($maxId, 5, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}