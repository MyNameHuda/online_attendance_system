@component('mail::message')
# Reset Password

Halo **{{ $name }}**,

Kami menerima permintaan untuk reset password akun Attendance Anda.

Klik tombol di bawah ini untuk mengatur password baru. Link ini berlaku selama **60 menit**.

@component('mail::button', ['url' => $resetUrl])
Reset Password Saya
@endcomponent

Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tetap aman.

---
Link tidak bisa diklik? Copy-paste URL ini ke browser:
{{ $resetUrl }}

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
