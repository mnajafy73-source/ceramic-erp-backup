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
    public function index(Request $request)
    {
        $query = Product::with('unit', 'parent', 'children');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sortField = in_array($request->input('sort'), ['code', 'name', 'status'])
                    ? $request->input('sort') : 'code';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $products = $query->orderBy($sortField, $sortDirection)->paginate(10)->appends($request->all());

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $units = Unit::all();
        $formulas = Formula::all();
        $packagings = Packaging::all();
        $allProducts = Product::orderBy('name')->get();
        return view('products.create', compact('units', 'formulas', 'packagings', 'allProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => 'required|string|max:50|unique:products,code',
            'name'          => 'required|string|max:255|unique:products,name',
            'unit_id'       => 'required|exists:units,id',
            'tonneli_feed_rate' => 'nullable|integer|min:0',
            'cavities'      => 'nullable|integer|min:1',
            'per_box'       => 'nullable|integer|min:0',
            'per_pack'      => 'nullable|integer|min:0',
            'per_pallet'    => 'nullable|integer|min:0',
            'layers_per_box'=> 'nullable|integer|min:0',
            'status'        => 'boolean',
            'in_production' => 'boolean',
            'weight'        => 'nullable|numeric|min:0',
            'formula_id'    => 'nullable|exists:formulas,id',
            'carton_packaging_id' => 'nullable|exists:packagings,id',
            'layer_packaging_id'  => 'nullable|exists:packagings,id',
            'firing_process' => 'nullable|in:tonneli,shuttle,both',
            'parent_product_id' => 'nullable|exists:products,id',
            'product_type'  => 'nullable|in:normal,injection', // ✅ اضافه شد
        ]);

        if (empty($validated['firing_process'])) {
            $validated['firing_process'] = 'tonneli';
        }
        if (empty($validated['product_type'])) {
            $validated['product_type'] = 'normal'; // مقدار پیش‌فرض
        }

        $validated['status'] = $request->has('status');
        $validated['in_production'] = $request->has('in_production');
        $validated['cavities'] = $validated['cavities'] ?? 1;
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

        return redirect()->route('products.index')
            ->with('success', 'کالا با موفقیت ایجاد شد.');
    }

    public function show(Product $product)
    {
        $product->load('unit', 'logs.user', 'parent', 'children', 'aliases');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $units = Unit::all();
        $formulas = Formula::all();
        $packagings = Packaging::all();
        $allProducts = Product::where('id', '!=', $product->id)->orderBy('name')->get();
        $product->load('aliases', 'parent', 'children');
        return view('products.edit', compact('product', 'units', 'formulas', 'packagings', 'allProducts'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:products,name,' . $product->id,
            'unit_id'       => 'required|exists:units,id',
            'tonneli_feed_rate' => 'nullable|integer|min:0',
            'cavities'      => 'nullable|integer|min:1',
            'per_box'       => 'nullable|integer|min:0',
            'per_pack'      => 'nullable|integer|min:0',
            'per_pallet'    => 'nullable|integer|min:0',
            'layers_per_box'=> 'nullable|integer|min:0',
            'status'        => 'boolean',
            'in_production' => 'boolean',
            'weight'        => 'nullable|numeric|min:0',
            'formula_id'    => 'nullable|exists:formulas,id',
            'carton_packaging_id' => 'nullable|exists:packagings,id',
            'layer_packaging_id'  => 'nullable|exists:packagings,id',
            'firing_process' => 'nullable|in:tonneli,shuttle,both',
            'parent_product_id' => 'nullable|exists:products,id',
            'product_type'  => 'nullable|in:normal,injection', // ✅ اضافه شد
        ]);

        if (empty($validated['firing_process'])) {
            $validated['firing_process'] = $product->firing_process ?? 'tonneli';
        }
        if (empty($validated['product_type'])) {
            $validated['product_type'] = $product->product_type ?? 'normal';
        }

        unset($validated['code']);
        $validated['status'] = $request->has('status');
        $validated['in_production'] = $request->has('in_production');
        $validated['cavities'] = $validated['cavities'] ?? $product->cavities;
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

        return redirect()->route('products.index')
            ->with('success', 'کالا با موفقیت ویرایش شد.');
    }

    public function toggleStatus(Product $product)
    {
        $product->status = !$product->status;
        $product->save();
        return response()->json(['success' => true, 'status' => $product->status]);
    }

    public function toggleInProduction(Product $product)
    {
        $product->in_production = !$product->in_production;
        $product->save();
        return response()->json(['success' => true, 'in_production' => $product->in_production]);
    }

    public function destroy(Product $product)
    {
        try {
            $product->aliases()->delete();
            $product->delete();
            return redirect()->route('products.index')->with('success', 'کالا حذف شد.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }
    }
}