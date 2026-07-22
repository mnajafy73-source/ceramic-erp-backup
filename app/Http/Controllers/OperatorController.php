<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function index()
    {
        $operators = Operator::latest()->paginate(10);
        return view('operators.index', compact('operators'));
    }

    public function create()
    {
        return view('operators.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:operators,name',
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->has('status');

        Operator::create($validated);

        return redirect()->route('operators.create')
            ->with('success', 'اپراتور جدید با موفقیت ایجاد شد. می‌توانید اپراتور بعدی را وارد کنید.');
    }

    public function show(Operator $operator)
    {
        return view('operators.show', compact('operator'));
    }

    public function edit(Operator $operator)
    {
        return view('operators.edit', compact('operator'));
    }

    public function update(Request $request, Operator $operator)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:operators,name,' . $operator->id,
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->has('status');

        $operator->update($validated);

        return redirect()->route('operators.index')
            ->with('success', 'اپراتور با موفقیت ویرایش شد.');
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();

        return redirect()->route('operators.index')
            ->with('success', 'اپراتور حذف شد.');
    }

    // متد جدید برای تغییر وضعیت از طریق AJAX
    public function toggleStatus(Operator $operator)
    {
        $operator->status = !$operator->status;
        $operator->save();

        return response()->json([
            'success' => true,
            'status'  => $operator->status,
        ]);
    }
}