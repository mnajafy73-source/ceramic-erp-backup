<?php

namespace App\Http\Controllers;

use App\Models\Formula;
use App\Models\RawMaterial;
use Illuminate\Http\Request;

class FormulaController extends Controller
{
    public function index()
    {
        $formulas = Formula::with('items.rawMaterial')->get();
        return view('formulas.index', compact('formulas'));
    }

    public function create()
    {
        $materials = RawMaterial::all();
        return view('formulas.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.percentage' => 'required|numeric|min:0|max:100',
        ]);

        $formula = Formula::create(['name' => $request->name]);

        foreach ($request->items as $item) {
            $formula->items()->create($item);
        }

        return redirect()->route('formulas.index')
            ->with('success', 'فرمول با موفقیت ثبت شد.');
    }

    public function edit(Formula $formula)
    {
        $materials = RawMaterial::all();
        $formula->load('items');
        return view('formulas.edit', compact('formula', 'materials'));
    }

    public function update(Request $request, Formula $formula)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.percentage' => 'required|numeric|min:0|max:100',
        ]);

        $formula->update(['name' => $request->name]);
        $formula->items()->delete();

        foreach ($request->items as $item) {
            $formula->items()->create($item);
        }

        return redirect()->route('formulas.index')
            ->with('success', 'فرمول با موفقیت ویرایش شد.');
    }

    public function destroy(Formula $formula)
    {
        $formula->delete();
        return redirect()->route('formulas.index')
            ->with('success', 'فرمول با موفقیت حذف شد.');
    }
}