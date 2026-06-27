<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $units = $query->orderBy('name')->paginate(10)->withQueryString();

        // products.unit stores the unit name as plain text rather than a
        // unit_id foreign key, so usage counts are matched by name here too.
        $units->getCollection()->transform(function ($unit) {
            $unit->products_count = Product::query()->where('unit', $unit->name)->count();
            return $unit;
        });

        $stats = Unit::selectRaw('COUNT(*) as total, SUM(status = "active") as active, SUM(status = "inactive") as inactive')->first();

        return view('frontend.product.units', [
            'units'         => $units,
            'totalUnits'    => $stats->total,
            'activeUnits'   => $stats->active,
            'inactiveUnits' => $stats->inactive,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units')->where('company_id', auth()->user()->company_id),
            ],
        ], [
            'name.unique' => 'A unit with this name already exists.',
        ]);

        $unit = Unit::query()->create([
            'company_id' => auth()->user()->company_id,
            'name'       => $request->name,
            'status'     => $request->status ?? 'active',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'unit' => $unit]);
        }

        return redirect()->back()->with('success', 'Unit created successfully');
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::query()->findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units')->where('company_id', auth()->user()->company_id)->ignore($id),
            ],
        ], [
            'name.unique' => 'A unit with this name already exists.',
        ]);

        // Products reference units by name (no unit_id FK), so renaming a
        // unit has to relabel every product currently using the old name —
        // otherwise they'd silently fall back to looking unassigned.
        $oldName = $unit->name;

        $unit->update([
            'name'   => $request->name,
            'status' => $request->status ?? $unit->status,
        ]);

        if ($oldName !== $unit->name) {
            Product::query()->where('unit', $oldName)->update(['unit' => $unit->name]);
        }

        return redirect()->back()->with('success', 'Unit updated successfully');
    }

    public function destroy($id)
    {
        $unit = Unit::query()->findOrFail($id);

        if (Product::query()->where('unit', $unit->name)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete unit because it is used by products.');
        }

        $unit->delete();

        return redirect()->back()->with('success', 'Unit deleted successfully');
    }
}
