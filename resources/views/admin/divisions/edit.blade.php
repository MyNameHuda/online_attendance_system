@extends('layouts.app')
@section('title', 'Edit Divisi')
@section('content')
<div class="max-w-md mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-5">
    <h2 class="font-semibold mb-4">Edit Divisi</h2>
    @if($errors->any())<div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.divisions.update', $division) }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium mb-1">Nama Divisi</label>
            <input name="name" value="{{ old('name', $division->name) }}" required class="w-full border border-slate-300 rounded px-3 py-2">
        </div>
        <div class="flex gap-2">
            <button class="bg-indigo-600 text-white px-4 py-2 rounded font-medium">Update</button>
            <a href="{{ route('admin.divisions.index') }}" class="px-4 py-2 rounded border border-slate-300 hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
@endsection
