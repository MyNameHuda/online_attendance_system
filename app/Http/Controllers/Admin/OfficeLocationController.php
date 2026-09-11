<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function index(): View
    {
        $office = OfficeLocation::getMain();
        return view('admin.office-locations.index', compact('office'));
    }

    public function edit(?OfficeLocation $officeLocation = null): View
    {
        $office = $officeLocation ?? OfficeLocation::getMain();
        return view('admin.office-locations.edit', compact('office'));
    }

    public function update(Request $request, ?OfficeLocation $officeLocation = null): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['nullable', 'string', 'max:255'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
        ]);
        $office = $officeLocation ?? OfficeLocation::getMain();
        if (! $office) {
            // Create main office if doesn't exist
            OfficeLocation::create(array_merge($data, ['is_main' => true]));
        } else {
            $office->update($data);
        }
        return redirect()->route('admin.office-locations.index')->with('success', 'Lokasi kantor utama berhasil diupdate.');
    }
}
