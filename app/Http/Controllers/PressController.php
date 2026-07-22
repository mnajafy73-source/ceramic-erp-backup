<?php

namespace App\Http\Controllers;

use App\Models\Press;
use Illuminate\Http\Request;

class PressController extends Controller
{
    public function index()
    {
        $presses = Press::latest()->paginate(10);
        return view('presses.index', compact('presses'));
    }

    public function create()
    {
        return view('presses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:presses,name',
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->has('status');

        Press::create($validated);

        return redirect()->route('presses.create')
            ->with('success', 'پرس جدید با موفقیت ایجاد شد. می‌توانید پرس بعدی را وارد کنید.');
    }

    public function show(Press $press)
    {
        return view('presses.show', compact('press'));
    }

    public function edit(Press $press)
    {
        return view('presses.edit', compact('press'));
    }

    public function update(Request $request, Press $press)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:presses,name,' . $press->id,
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->has('status');

        $press->update($validated);

        return redirect()->route('presses.index')
            ->with('success', 'پرس با موفقیت ویرایش شد.');
    }

    public function destroy(Press $press)
    {
        $press->delete();

        return redirect()->route('presses.index')
            ->with('success', 'پرس حذف شد.');
    }

    // متد جدید برای تغییر وضعیت از طریق AJAX
    public function toggleStatus(Press $press)
    {
        $press->status = !$press->status;
        $press->save();

        return response()->json([
            'success' => true,
            'status'  => $press->status,
        ]);
    }
}