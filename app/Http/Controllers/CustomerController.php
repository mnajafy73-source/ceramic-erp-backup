<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name')->paginate(20);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:customers,name',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'مشتری با موفقیت ایجاد شد.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:customers,name,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'مشتری با موفقیت ویرایش شد.');
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->status = !$customer->status;
        $customer->save();

        return response()->json([
            'success' => true,
            'status' => $customer->status,
        ]);
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
            return redirect()->route('customers.index')
                ->with('success', 'مشتری با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->route('customers.index')
                ->with('error', 'خطا در حذف مشتری: ' . $e->getMessage());
        }
    }
}