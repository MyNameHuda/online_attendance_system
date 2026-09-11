@extends('layouts.app')
@section('title', 'Edit Karyawan')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-5">
    <h2 class="font-semibold mb-4">Edit Karyawan: {{ $employee->name }}</h2>
    @if($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
        @csrf @method('PUT')
        @include('admin.employees.form')
        <div class="mt-4 flex gap-2">
            <button class="bg-indigo-600 text-white px-4 py-2 rounded font-medium">Update</button>
            <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 rounded border border-slate-300 hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
@endsection
