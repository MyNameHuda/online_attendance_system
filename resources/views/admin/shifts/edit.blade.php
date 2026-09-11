@extends('layouts.app')
@section('title', 'Edit Shift')
@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-5">
    <h2 class="font-semibold mb-4">Edit Shift: {{ $shift->name }}</h2>
    <form method="POST" action="{{ route('admin.shifts.update', $shift) }}">
        @csrf @method('PUT')
        @include('admin.shifts.form')
        <div class="mt-4 flex gap-2">
            <button class="bg-indigo-600 text-white px-4 py-2 rounded font-medium">Update</button>
            <a href="{{ route('admin.shifts.index') }}" class="px-4 py-2 rounded border border-slate-300 hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
@endsection
