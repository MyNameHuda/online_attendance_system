<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->query('q');
        $divisionFilter = $request->query('division_id');

        $employees = User::with('division')
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            }))
            ->when($divisionFilter, fn ($qb) => $qb->where('division_id', $divisionFilter))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $divisions = Division::orderBy('name')->get();
        return view('admin.employees.index', compact('employees', 'divisions', 'q', 'divisionFilter'));
    }

    public function create(): View
    {
        $divisions = Division::orderBy('name')->get();
        return view('admin.employees.create', compact('divisions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nik'         => ['required', 'string', 'max:50', 'unique:users,nik'],
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', 'string', Password::min(6)],
            'division_id' => ['required', 'exists:divisions,id'],
            'position'    => ['nullable', 'string', 'max:100'],
            'role'        => ['required', Rule::in([User::ROLE_KARYAWAN, User::ROLE_KADIV, User::ROLE_ADMIN])],
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('admin.employees.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function show(User $employee): View
    {
        $employee->load('division');
        return view('admin.employees.show', compact('employee'));
    }

    public function edit(User $employee): View
    {
        $divisions = Division::orderBy('name')->get();
        return view('admin.employees.edit', compact('employee', 'divisions'));
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'nik'         => ['required', 'string', 'max:50', 'unique:users,nik,' . $employee->id],
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email,' . $employee->id],
            'password'    => ['nullable', 'string', Password::min(6)],
            'division_id' => ['required', 'exists:divisions,id'],
            'position'    => ['nullable', 'string', 'max:100'],
            'role'        => ['required', Rule::in([User::ROLE_KARYAWAN, User::ROLE_KADIV, User::ROLE_ADMIN])],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $employee->update($data);

        return redirect()->route('admin.employees.index')->with('success', 'Karyawan berhasil diupdate.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        if ($employee->id === auth()->id()) {
            return back()->withErrors(['general' => 'Tidak bisa hapus akun sendiri.']);
        }
        $employee->delete();
        return redirect()->route('admin.employees.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}
