@if($errors->any())
    <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="grid grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium mb-1">Nama Shift</label>
        <input name="name" value="{{ old('name', $shift->name ?? '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Jam Masuk</label>
        <input name="start_time" type="time" value="{{ old('start_time', isset($shift) ? substr($shift->start_time,0,5) : '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Jam Pulang</label>
        <input name="end_time" type="time" value="{{ old('end_time', isset($shift) ? substr($shift->end_time,0,5) : '') }}" required class="w-full border border-slate-300 rounded px-3 py-2">
    </div>
</div>
