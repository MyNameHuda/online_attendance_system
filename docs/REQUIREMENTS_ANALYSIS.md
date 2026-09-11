# Laporan Analisis Kebutuhan — Online Attendance System

> **Sumber**: `WEB DEVELOPER – ONLINE ATTENDANCE SYSTEM.pdf`
> **Durasi pengerjaan**: 3–4 jam
> **Total nilai**: 100 poin
> **Tujuan**: Dokumen acuan untuk tahap implementasi web

---

## 1. Ringkasan Proyek

Sistem absensi online berbasis web untuk karyawan dengan fitur inti:
- Absen masuk & pulang (dengan foto + GPS)
- Manajemen jadwal & shift
- Tukar shift & tukar hari libur (dengan alur approval)
- Multi-role: Karyawan, Kepala Divisi, Admin/HRD

Stack teknologi **fleksibel** ("sesuai bahasa pemograman yang dikuasai"), sehingga keputusan stack jadi keputusan arsitektur utama yang harus diambil di awal.

---

## 2. Role & Hak Akses (Authorization Matrix)

| Fitur | Karyawan | Kepala Divisi | Admin/HRD |
|---|:---:|:---:|:---:|
| Absen masuk/pulang | ✅ | ✅ (juga karyawan) | ✅ (juga karyawan) |
| Lihat jadwal sendiri | ✅ | ✅ | ✅ |
| Lihat riwayat absensi sendiri | ✅ | ✅ | ✅ |
| Lihat anggota divisinya | — | ✅ | — |
| Lihat seluruh karyawan | — | — | ✅ |
| Lihat absensi anggota divisi | — | ✅ | — |
| Lihat seluruh absensi | — | — | ✅ |
| Atur jadwal & shift | — | — | ✅ |
| Ajukan tukar shift / libur | ✅ | ✅ (juga karyawan) | ✅ (juga karyawan) |
| **ACC pengajuan tukar shift** (sebagai target) | ✅ | ✅ | ✅ |
| Approve/Reject tukar shift (KD step) | — | ✅ (divisinya) | — |
| Approve/Reject tukar libur | — | ✅ (divisinya) | — |
| Lihat status pengajuan sendiri | ✅ | ✅ | ✅ |
| Lihat seluruh pengajuan | — | — | ✅ |

**Catatan penting**:
- Setiap role juga tetap "karyawan" — bisa absen dan mengajukan tukar.
- Kepala Divisi = siapapun `role = kepala_divisi` di divisi tsb. Boleh lebih dari 1 orang KD per divisi (Wakil, Plh, dll). Semua KD di 1 divisi punya hak approve yang sama.
- Kepala Divisi **hanya** boleh approve karyawan divisinya sendiri. Harus di-enforce via Middleware/Policy/Gate, bukan hanya disembunyikan di UI.
- HRD tidak approve swap — hanya melihat semua data (read-only untuk pengajuan).

---

## 3. Rincian Fitur & Bobot Nilai

| # | Fitur | Nilai | Kompleksitas | Catatan Kritis |
|---|---|:---:|:---:|---|
| 1 | Data Karyawan | 10 | Rendah | Relasi: Division hasMany Employee |
| 2 | Data Shift | 10 | Rendah | Contoh: Pagi/Siang/Malam |
| 3 | Jadwal Kerja | 10 | Sedang | Status: Kerja / Libur, hanya lihat sendiri |
| 4 | Absensi Masuk | 15 | **Tinggi** | Wajib foto + GPS (browser geolocation) |
| 5 | Absensi Pulang | 10 | Sedang | Tergantung data absen masuk |
| 6 | Validasi Absensi | 10 | Sedang | 3 rule anti-cheat |
| 7 | Tukar Shift | 15 | **Tinggi** | Auto-swap jadwal saat approved |
| 8 | Tukar Hari Libur | 10 | Sedang | Auto-update jadwal saat approved |
| 9 | Approval Kepala Divisi (scope) | 5 | Rendah | Middleware/Policy/Gate |
| 10 | Dashboard | 5 | Rendah | Tampilan info hari ini + ringkasan |
| | **Total** | **100** | | |

> **Insight**: 4 fitur terbesar (Total 55 poin) adalah Absen Masuk, Tukar Shift, Absensi Pulang, dan Validasi. Itu sweet spot untuk optimasi nilai.

---

## 4. Detail Entitas Data (Inferred Schema)

### 4.1 Division
- `id`, `name` (mis. "IT", "Finance", "HRD")
- Relasi: `hasMany` Employee, `hasMany` Schedule

### 4.2 Employee (User)
- `nik` (unique)
- `name`
- `email` (unique, untuk login)
- `password` (hashed)
- `division_id` (FK)
- `position` (Jabatan: Staff, Supervisor, Manager, dll)
- `role` (enum: `karyawan` | `kepala_divisi` | `admin`)
- Relasi: `belongsTo` Division, `hasMany` Attendance, `hasMany` Schedule

### 4.3 Shift
- `name` (Pagi/Siang/Malam)
- `start_time` (time, mis. 08:00)
- `end_time` (time, mis. 16:00)

### 4.4 Schedule (Jadwal Kerja)
- `employee_id` (FK)
- `date` (date)
- `shift_id` (FK, nullable jika libur)
- `status` (enum: `kerja` | `libur`)

### 4.5 Attendance (Absensi)
- `employee_id` (FK)
- `date` (date)
- `clock_in_time` (datetime, nullable)
- `clock_in_photo` (path, nullable)
- `clock_in_lat` (decimal, nullable)
- `clock_in_lng` (decimal, nullable)
- `clock_out_time` (datetime, nullable)
- `clock_out_photo` (path, nullable)
- `clock_out_lat` (decimal, nullable)
- `clock_out_lng` (decimal, nullable)
- Unik: (`employee_id`, `date`)

### 4.6 ShiftSwapRequest (Updated — 2-step approval)
- `requester_id` (FK → employee, yang mengajukan)
- `target_employee_id` (FK → employee, yang diajak tukar)
- `date` (date)
- `requester_shift_id` (FK → shift, shift Andi di tanggal tsb)
- `target_shift_id` (FK → shift, shift Budi di tanggal tsb)
- `reason` (text, alasan Andi ajukan)
- `target_response_note` (text, nullable, alasan Budi terima/tolak)
- `approver_response_note` (text, nullable, alasan KD approve/reject)
- `status` (enum):
  - `pending_target` — menunggu ACC Budi
  - `pending_kadiv` — Budi sudah ACC, menunggu ACC KD
  - `approved` — final, jadwal sudah ditukar
  - `rejected` — final (ditolak Budi atau KD)
  - `cancelled` — Andi cancel sebelum Budi ACC
- `target_responded_at` (datetime, nullable)
- `approver_id` (FK → employee, KD yang approve/reject, nullable)
- `approver_decided_at` (datetime, nullable)

**Status flow diagram:**
```
[Andi create] → pending_target
                    │
        ┌───────────┼───────────┐
   [Budi accept]  [Budi reject]  [Andi cancel]
        │              │              │
pending_kadiv     rejected      cancelled
        │
   ┌────┼────┐
[KD approve]  [KD reject]
   │             │
approved     rejected
```

### 4.7 DayOffSwapRequest
- `requester_id` (FK → employee)
- `old_off_date` (date)
- `new_off_date` (date)
- `reason` (text)
- `status` (enum: `pending` | `approved` | `rejected`)
- `approver_id` (FK → employee, nullable)
- `approved_at` (datetime, nullable)

---

## 5. Aturan Bisnis Kritis

### 5.1 Absen Masuk
- **Wajib foto** saat submit (capture via webcam, atau upload file).
- **Wajib GPS** via `navigator.geolocation.getCurrentPosition`.
- Disimpan ke baris attendance hari itu (create if not exists, else update).

### 5.2 Absen Pulang
- Hanya bisa dilakukan **setelah** absen masuk di hari yang sama.
- Update field `clock_out_*` saja, jangan reset clock_in.

### 5.3 Validasi Absensi (3 anti-cheat rules)
1. **Tidak boleh absen masuk 2x** dalam hari yang sama (cek `clock_in_time` not null).
2. **Tidak boleh absen pulang 2x** dalam hari yang sama (cek `clock_out_time` not null).
3. **Tidak boleh absen di hari libur** (cek Schedule.status == 'libur' untuk hari tsb).

### 5.4 Tukar Shift — Flow (2-Step Approval)

**Step 1: Pengajuan (Andi)**
1. Andi (A) buat pengajuan ke Budi (B) untuk tanggal & shift tertentu.
2. **Validasi wajib**:
   - A dan B harus **divisi yang sama** (cek `division_id`).
   - Tanggal harus ada di Schedule A (status = `kerja`) dan Schedule B (status = `kerja`). Kalau salah satu libur, tolak.
   - Tidak ada request lain yang masih `pending_target` atau `pending_kadiv` untuk tanggal yang sama dari A atau B.
3. Status awal: `pending_target`.

**Step 2: ACC Target (Budi)**
4. Budi login → lihat notifikasi/inbox → bisa **Accept** atau **Reject**.
5. **Jika Accept**:
   - Status → `pending_kadiv`
   - `target_responded_at` = now
   - `target_response_note` = optional
6. **Jika Reject**:
   - Status → `rejected` (final)
7. Andi juga bisa **Cancel** selama masih `pending_target` → status `cancelled`.

**Step 3: Approval KD**
8. Semua Kepala Divisi di divisi A (yang = divisi B) bisa lihat & approve.
9. KD bisa **Approve** atau **Reject**.
10. **Jika Approve** (status `pending_kadiv` → `approved`):
    - `Schedule` A di tanggal tsb → shift B
    - `Schedule` B di tanggal tsb → shift A
    - `approver_id` = KD yang approve, `approver_decided_at` = now
    - Semua dalam 1 DB transaction (atomic, rollback jika gagal).
11. **Jika Reject**:
    - Status → `rejected` (final), `approver_response_note` = alasan.

**Catatan**: Status `pending_kadiv` masih bisa dilihat oleh Andi/Budi sebagai "menunggu approval atasan". Setelah final (`approved`/`rejected`/`cancelled`), tidak ada perubahan lagi.

### 5.5 Tukar Hari Libur — Flow
1. Karyawan ajukan tukar tanggal libur.
2. **Validasi wajib**: `old_off_date` harus Schedule.status = `libur` untuk karyawan tsb.
3. **Validasi tambahan**: `new_off_date` harus Schedule.status = `kerja` untuk karyawan tsb (kalau juga libur, tidak ada yang ditukar).
4. Status awal: `pending`.
5. Kepala Divisi divisi karyawan approve/reject.
6. Jika **approved**: `old_off_date` jadi `kerja`, `new_off_date` jadi `libur`, dalam 1 transaction.

### 5.6 Scope Approval
- Kepala Divisi hanya boleh melihat & approve pengajuan dari karyawan **divisinya sendiri**.
- Wajib pakai Middleware/Policy/Gate (bukan hanya hidden di UI).

### 5.7 Geofencing (Final)
- Setiap divisi punya `office_location` (lat, lng, radius_meters, default 200m).
- Saat karyawan submit absen (masuk/pulang):
  1. Ambil `geolocation` dari browser.
  2. Hitung Haversine distance ke `office_location` divisinya.
  3. **Jika distance > radius** → tolak, tampilkan error "Anda di luar jangkauan kantor (X meter dari titik absen)".
  4. **Jika OK** → lanjut simpan ke DB.
- **Jika geolocation gagal** (browser tolak / timeout / no signal) → **hard-fail**, tampilkan "Tidak dapat membaca lokasi. Mohon izinkan akses GPS dan coba lagi." (tidak ada bypass).

---

## 6. Keputusan yang Sudah Diambil (Final)

| Topik | Keputusan |
|---|---|
| **Stack** | Laravel 11 + Blade + Tailwind (via Breeze) + SQLite |
| **Database** | SQLite file-based (zero-config, portabel) |
| **Auth** | Laravel Breeze (email + password, session-based) |
| **Frontend** | Server-rendered Blade + Tailwind, mobile-responsive |
| **Foto** | Upload via `<input type="file" capture="environment">` (mobile camera) ATAU webcam via getUserMedia (desktop) → simpan di `storage/app/public/attendance/`, path di DB |
| **Geofencing** | **WAJIB** — validasi Haversine distance dari titik kantor divisi karyawan |
| **Konfigurasi Kantor** | Tabel `office_locations`: per-divisi (`division_id`, `lat`, `lng`, `radius_meters`, default 200m) |
| **Tukar Shift — Rule** | Hanya boleh antara karyawan **divisi yang sama**. Approval oleh Kepala Divisi divisi tersebut (1 orang, sama untuk keduanya). |
| **Tukar Shift — Auto-swap** | Immediate di transaction DB saat approved |
| **Jadwal** | Per-tanggal, 1 record = 1 hari 1 karyawan |
| **Hari Libur** | Ditandai via Schedule.status = `libur`, dikelola Admin per-record |
| **Validasi telat** | Tidak ada (PDF tidak sebut). Hanya validasi "sudah/belum absen" |
| **Notifikasi** | Tidak ada (di luar scope poin) |
| **Approval flow tukar libur** | Kepala Divisi divisi karyawan yang mengajukan |
| **Geolocation gagal** | Hard-fail (tidak ada bypass, harus enable GPS) |
| **Cancel pengajuan** | Pemohon bisa cancel selama masih `pending` |
| **Tanggal lampau** | Tidak boleh submit absen / pengajuan untuk tanggal sebelum hari ini |
| **Tukar Shift — 2 step** | Step 1: ACC target (Budi). Step 2: Approve KD. Status: `pending_target` → `pending_kadiv` → `approved`/`rejected`/`cancelled` |
| **Multiple KD per divisi** | Boleh. Semua KD di 1 divisi punya hak approve yang sama (siapa duluan yang approve) |
| **KD/Admin juga karyawan** | Bisa absen masuk/pulang, bisa juga mengajukan swap (sebagai karyawan) |
| **KD/HRD behavior saat jadi target** | Sama seperti karyawan biasa — bisa accept/reject swap request |

---

## 7. Pertanyaan Kritis yang Perlu Dijawab

~~Sebelum mulai coding, ada beberapa keputusan yang materially mengubah struktur project:~~

**Semua pertanyaan sudah terjawab** (lihat Bagian 6). Ringkasan final:
1. ✅ Stack: Laravel 11 + Blade + SQLite
2. ✅ Frontend: server-rendered Blade + Tailwind
3. ✅ Geofencing: WAJIB, radius dari `office_location` per-divisi
4. ✅ Foto: upload via input file + capture attribute (mobile), webcam fallback (desktop)
5. ✅ KD mapping: cukup `role` field (Division.head_employee_id TIDAK dipakai)
6. ✅ Tukar Shift: 2-step approval (target ACC dulu, baru KD)
7. ✅ Jadwal: per-tanggal
8. ✅ Hari libur: via Schedule.status, dikelola admin
9. ✅ Multiple KD per divisi: boleh (semua KD di 1 divisi punya hak approve)
10. ✅ KD/Admin juga karyawan: bisa absen, bisa ajukan swap, bisa jadi target swap

### Default yang dipakai (tidak perlu konfirmasi lagi):
- **Default radius geofence**: 200 meter per divisi
- **Geolocation gagal**: hard-fail
- **Seeder**: 3 divisi (IT, Finance, HRD), 1-2 KD per divisi + 2-3 karyawan, 3 shift default (Pagi/Siang/Malam), jadwal 14 hari ke depan
- **Cancel swap**: pemohon bisa cancel selama masih `pending_target`
- **Tanggal lampau**: tidak boleh diabsen / di-swap
- **HRD tidak approve swap** (read-only untuk pengajuan)

---

## 8. Rekomendasi Pendekatan (High-Level)

Untuk durasi 3-4 jam, urutan eksekusi yang memaksimalkan nilai:

1. **Setup + Skema + Seeder** (Karyawan, Divisi, Shift, Office Location) → 10 poin (fitur 1) + foundation
2. **CRUD Shift & Jadwal** (admin) → 20 poin (fitur 2-3)
3. **Absen Masuk + Pulang + Validasi + Geofencing** → 35 poin (fitur 4-6) ← **sweet spot**
4. **Tukar Shift (2-step: target ACC → KD approve + auto-swap) + Tukar Libur (1-step KD approve) + Approval scope (Middleware/Policy)** → 30 poin (fitur 7-9) ← fokus logika status & transaction
5. **Dashboard** → 5 poin (fitur 10)

Total = 100 poin. Target: implementasi basic tapi lengkap untuk semua 10 fitur, dengan depth lebih di fitur bernilai besar (Absen 15poin + Tukar Shift 15poin).

---

## 9. Schema Update (Final)

Tambahan karena keputusan geofencing:

### 4.8 OfficeLocation (Baru)
- `division_id` (FK, unique — 1 lokasi per divisi)
- `name` (mis. "Kantor Pusat IT")
- `latitude` (decimal)
- `longitude` (decimal)
- `radius_meters` (integer, default 200)

---

**Dokumen ini siap jadi acuan** untuk step pembangunan web. Silakan di-review, kalau ada yang mau diubah/tambah, kasih tau aja. Kalau udah oke, kasih aba-aba dan gw mulai bangun.
