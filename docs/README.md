# Online Attendance System

Sistem absensi online untuk karyawan — built with Laravel 11 + SQLite + Tailwind + Alpine.js + Leaflet.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend  | Laravel 11 (PHP 8.2) |
| Database | SQLite (file-based) |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js |
| Map      | Leaflet 1.9.4 + OpenStreetMap |
| Auth     | Custom session-based |

## Fitur

- **Absensi** dengan foto selfie + verifikasi GPS dalam radius kantor (default 200m)
- **Tukar Shift** antar karyawan satu divisi (auto-approve begitu target ACC)
- **Tukar Hari Libur** (auto-apply saat pengajuan dibuat)
- **Multi-User Schedule** — admin input jadwal untuk banyak karyawan sekaligus
- **Hari Libur Nasional** dengan broadcast notification
- **Laporan Bulanan** + export CSV
- **Audit Log** untuk semua aksi penting
- **Notifikasi** in-app + email (bell icon dengan unread badge)
- **Reset Password** self-service via email link (60 min expiry)
- **Multi-Role Dashboard** — Admin/HRD, Kepala Divisi, Karyawan (scoped)

## Quick Start

```bash
# 1. Install dependencies
cd attendance-app
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

# 3. Run dev server
php artisan serve
# Akses: http://127.0.0.1:8000
```

## Default Demo Accounts

| Email | Password | Role | Divisi |
|-------|----------|------|--------|
| admin@attendance.test | password123 | admin | HRD |
| budi.kadiv@attendance.test | password123 | kepala_divisi | IT |
| siti.kadiv@attendance.test | password123 | kepala_divisi | Finance |
| andi@attendance.test | password123 | karyawan | IT |
| hendra@attendance.test | password123 | karyawan | IT |
| citra@attendance.test | password123 | karyawan | IT |
| dedi@attendance.test | password123 | karyawan | IT |
| eko@attendance.test | password123 | karyawan | Finance |
| fitri@attendance.test | password123 | karyawan | Finance |
| gita@attendance.test | password123 | karyawan | HRD |

## Testing

```bash
# Jalankan semua test dari project root
cd ..
for f in test-*.php; do php "$f"; done
```

8 test files covering business logic, attendance, notifications, audit log, multi-user schedule, admin views, login + shift swap, status badge, KD attendance scoping.

## Dokumentasi

Lihat `MANUAL_BOOK_Online_Attendance_System.docx` untuk user manual lengkap (Bahasa Indonesia) — mencakup semua role, alur fitur, dan troubleshooting.

## Struktur Direktori

```
attendance-app/
├── app/
│   ├── Http/Controllers/         (semua controller: Admin/, Auth/, Kadiv/)
│   ├── Models/                   (Eloquent models)
│   ├── Services/                 (NotificationService)
│   └── Middleware/              (RoleMiddleware)
├── database/
│   ├── migrations/               (schema)
│   └── seeders/                  (DatabaseSeeder)
├── resources/views/              (Blade templates)
├── routes/web.php                (semua route definitions)
├── storage/app/public/           (uploaded photos)
└── .env                          (environment config — NOT in git)

# Tests di project root
test-*.php
```

## Lisensi

Internal use only — see `MANUAL_BOOK_Online_Attendance_System.docx` appendix.
