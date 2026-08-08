<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {
        $materials = RawMaterial::all();
        return view('raw-materials.index', compact('materials'));
    }

    public function create()
    {
        return view('raw-materials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,ton',
        ]);

        RawMaterial::create([
            'name' => $request->name,
            'unit' => $request->unit,
            'stock' => 0,
        ]);

        return redirect()->route('raw-materials.index')
            ->with('success', 'ماده اولیه با موفقیت ثبت شد.');
    }

    public function edit(RawMaterial $rawMaterial)
    {
        return view('raw-materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,ton',
        ]);

        $rawMaterial->update($request->all());
        return redirect()->route('raw-materials.index')
            ->with('success', 'ماده اولیه با موفقیت ویرایش شد.');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        $rawMaterial->delete();
        return redirect()->route('raw-materials.index')
            ->with('success', 'ماده اولیه با موفقیت حذف شد.');
    }
}