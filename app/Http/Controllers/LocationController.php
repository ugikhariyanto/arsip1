<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('locations.index', [
            'locations' => Location::query()->withCount('inventoryItems')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('locations.form', [
            'location' => new Location,
            'title' => 'Tambah lokasi',
            'submitLabel' => 'Simpan lokasi',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($this->validatedData($request));

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Location $location): View
    {
        return view('locations.form', [
            'location' => $location,
            'title' => 'Ubah lokasi',
            'submitLabel' => 'Simpan perubahan',
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $this->validatedData($request, $location);
        $previousName = $location->name;

        $location->update($data);

        if ($previousName !== $location->name) {
            $location->inventoryItems()->update(['location' => $location->name]);
        }

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->inventoryItems()->exists()) {
            return redirect()->route('locations.index')
                ->with('error', 'Lokasi tidak dapat dihapus karena masih digunakan oleh barang.');
        }

        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Location $location = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('locations', 'name')->ignore($location)],
        ]);
    }
}
