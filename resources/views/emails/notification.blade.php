@component('mail::message')
# {{ $n->title }}

{{ $n->message }}

@if($n->action_url)
@component('mail::button', ['url' => $n->action_url])
Lihat Detail
@endcomponent
@endif

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
