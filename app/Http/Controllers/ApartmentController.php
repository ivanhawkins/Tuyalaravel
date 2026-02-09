<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Building;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function index()
    {
        $apartments = Apartment::with('building')->get();
        return view('apartments.index', compact('apartments'));
    }

    public function create()
    {
        $buildings = Building::where('active', true)->get();
        return view('apartments.create', compact('buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'number' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
        ]);

        Apartment::create($validated);

        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento creado correctamente');
    }

    public function edit(Apartment $apartment)
    {
        $buildings = Building::where('active', true)->get();
        return view('apartments.edit', compact('apartment', 'buildings'));
    }

    public function update(Request $request, Apartment $apartment)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'number' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $apartment->update($validated);

        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento actualizado correctamente');
    }

    public function destroy(Apartment $apartment)
    {
        $apartment->delete();
        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento eliminado correctamente');
    }
}
