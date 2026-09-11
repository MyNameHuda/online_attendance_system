<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(): View
    {
        $divisions = Division::withCount('employees')->orderBy('name')->get();
        return view('admin.divisions.index', compact('divisions'));
    }

    public function create(): View
    {
        return view('admin.divisions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:divisions,name']]);
        Division::create($data);
        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division): View
    {
        return view('admin.divisions.edit', compact('division'));
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:divisions,name,' . $division->id]]);
        $division->update($data);
        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil diupdate.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        if ($division->employees()->exists()) {
            return back()->withErrors(['general' => 'Tidak bisa hapus divisi yang masih punya karyawan.']);
        }
        $division->delete();
        return redirect()->route('admin.divisions.index')->with('success', 'Divisi berhasil dihapus.');
    }
}
