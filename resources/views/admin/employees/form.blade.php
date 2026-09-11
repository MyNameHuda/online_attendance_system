{{-- Reusable form for create & edit --}}
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">NIK <span class="text-red-500">*</span></label>
            <input name="nik" value="{{ old('nik', $employee->nik ?? '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Nama <span class="text-red-500">*</span></label>
            <input name="name" value="{{ old('name', $employee->name ?? '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email <span class="text-red-500">*</span></label>
            <input name="email" type="email" value="{{ old('email', $employee->email ?? '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password {{ isset($employee) ? '(kosongkan jika tidak ubah)' : '*' }}</label>
            <input name="password" type="password" {{ isset($employee) ? '' : 'required' }} class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Divisi <span class="text-red-500">*</span></label>
            <select name="division_id" required class="w-full border border-slate-300 rounded px-3 py-2">
                @foreach($divisions as $d)
                    <option value="{{ $d->id }}" @selected(old('division_id', $employee->division_id ?? '') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Jabatan</label>
            <input name="position" value="{{ old('position', $employee->position ?? '') }}" class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Role <span class="text-red-500">*</span></label>
            <select name="role" required class="w-full border border-slate-300 rounded px-3 py-2">
                <option value="karyawan" @selected(old('role', $employee->role ?? '') == 'karyawan')>Karyawan</option>
                <option value="kepala_divisi" @selected(old('role', $employee->role ?? '') == 'kepala_divisi')>Kepala Divisi</option>
                <option value="admin" @selected(old('role', $employee->role ?? '') == 'admin')>Admin / HRD</option>
            </select>
        </div>
    </div>
</div>
