<?php

namespace App\Http\Controllers;

use App\Models\Packaging;
use Illuminate\Http\Request;

class PackagingController extends Controller
{
    public function index()
    {
        $packagings = Packaging::all();
        return view('packagings.index', compact('packagings'));
    }

    public function create()
    {
        return view('packagings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:carton,layer',
            'name' => 'required|string|max:255',
        ]);

        Packaging::create([
            'type' => $request->type,
            'name' => $request->name,
            'stock' => 0,
        ]);

        return redirect()->route('packagings.index')
            ->with('success', 'کارتن/لایه با موفقیت ثبت شد.');
    }

    public function edit(Packaging $packaging)
    {
        return view('packagings.edit', compact('packaging'));
    }

    public function update(Request $request, Packaging $packaging)
    {
        $request->validate([
            'type' => 'required|in:carton,layer',
            'name' => 'required|string|max:255',
        ]);

        $packaging->update($request->all());
        return redirect()->route('packagings.index')
            ->with('success', 'کارتن/لایه با موفقیت ویرایش شد.');
    }

    public function destroy(Packaging $packaging)
    {
        $packaging->delete();
        return redirect()->route('packagings.index')
            ->with('success', 'کارتن/لایه با موفقیت حذف شد.');
    }
}