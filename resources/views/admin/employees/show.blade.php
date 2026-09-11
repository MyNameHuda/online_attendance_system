@extends('layouts.app')
@section('title', 'Detail Karyawan')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-5">
    <h2 class="font-semibold mb-3">Detail Karyawan</h2>
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <div><dt class="text-xs text-slate-500">NIK</dt><dd class="font-mono">{{ $employee->nik }}</dd></div>
        <div><dt class="text-xs text-slate-500">Nama</dt><dd>{{ $employee->name }}</dd></div>
        <div><dt class="text-xs text-slate-500">Email</dt><dd>{{ $employee->email }}</dd></div>
        <div><dt class="text-xs text-slate-500">Divisi</dt><dd>{{ $employee->division->name }}</dd></div>
        <div><dt class="text-xs text-slate-500">Jabatan</dt><dd>{{ $employee->position ?? '-' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Role</dt><dd>{{ ucfirst(str_replace('_', ' ', $employee->role)) }}</dd></div>
    </dl>
    <a href="{{ route('admin.employees.index') }}" class="inline-block mt-4 text-indigo-600 text-sm">← Kembali</a>
</div>
@endsection
