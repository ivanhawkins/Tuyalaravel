<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::withCount('apartments')->get();
        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'tuya_client_id' => 'required|string',
            'tuya_client_secret' => 'required|string',
        ]);

        Building::create($validated);

        return redirect()->route('buildings.index')
            ->with('success', 'Edificio creado correctamente');
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'tuya_client_id' => 'required|string',
            'tuya_client_secret' => 'required|string',
            'active' => 'boolean',
        ]);

        $building->update($validated);

        return redirect()->route('buildings.index')
            ->with('success', 'Edificio actualizado correctamente');
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return redirect()->route('buildings.index')
            ->with('success', 'Edificio eliminado correctamente');
    }
}
