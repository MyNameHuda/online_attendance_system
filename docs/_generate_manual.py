"""
Generate user manual DOCX untuk Online Attendance System.
Style: Modern corporate — Calibri family, navy accent, A4, professional.
"""

from docx import Document
from docx.shared import Pt, Cm, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING
from docx.enum.table import WD_ALIGN_VERTICAL, WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
from datetime import date

# ============================================================================
# COLOR & FONT CONSTANTS
# ============================================================================
NAVY = RGBColor(0x2F, 0x54, 0x96)
INDIGO = RGBColor(0x63, 0x66, 0xF1)
SLATE_900 = RGBColor(0x0F, 0x17, 0x2A)
SLATE_700 = RGBColor(0x33, 0x41, 0x55)
SLATE_600 = RGBColor(0x47, 0x55, 0x69)
SLATE_500 = RGBColor(0x64, 0x74, 0x8B)
EMERALD = RGBColor(0x05, 0x96, 0x69)
AMBER = RGBColor(0xD9, 0x77, 0x06)
RED = RGBColor(0xDC, 0x26, 0x26)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
LIGHT_BG = "F4F6FB"  # hex string for shading

# ============================================================================
# DOCUMENT INIT + STYLES
# ============================================================================
doc = Document()

# Page setup A4, 1 inch margins
section = doc.sections[0]
section.page_height = Cm(29.7)
section.page_width = Cm(21.0)
section.top_margin = Cm(2.5)
section.bottom_margin = Cm(2.5)
section.left_margin = Cm(2.5)
section.right_margin = Cm(2.5)
section.header_distance = Cm(1.25)
section.footer_distance = Cm(1.25)

# Default style
style_normal = doc.styles['Normal']
style_normal.font.name = 'Calibri'
style_normal.font.size = Pt(11)
style_normal.paragraph_format.line_spacing_rule = WD_LINE_SPACING.MULTIPLE
style_normal.paragraph_format.line_spacing = 1.15
style_normal.paragraph_format.space_after = Pt(6)

# Heading styles — ModernCorporate look
def style_heading(level, size_pt, color=NAVY):
    s = doc.styles[f'Heading {level}']
    s.font.name = 'Calibri'
    s.font.size = Pt(size_pt)
    s.font.bold = True
    s.font.color.rgb = color
    s.paragraph_format.space_before = Pt(18 if level <= 2 else 12)
    s.paragraph_format.space_after = Pt(6)
    s.paragraph_format.keep_with_next = True

style_heading(1, 22, NAVY)
style_heading(2, 16, NAVY)
style_heading(3, 13, SLATE_700)
style_heading(4, 11, SLATE_600)

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================
def shade_cell(cell, color_hex):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement('w:shd')
    shd.set(qn('w:fill'), color_hex)
    tc_pr.append(shd)

def add_paragraph(text, style=None, bold=False, italic=False, color=None,
                  size=None, align=None, space_before=None, space_after=None,
                  indent_left=None, line_spacing=None):
    p = doc.add_paragraph()
    if style:
        p.style = doc.styles[style]
    if align is not None:
        p.alignment = align
    if space_before is not None:
        p.paragraph_format.space_before = Pt(space_before)
    if space_after is not None:
        p.paragraph_format.space_after = Pt(space_after)
    if indent_left is not None:
        p.paragraph_format.left_indent = Cm(indent_left)
    if line_spacing is not None:
        p.paragraph_format.line_spacing = line_spacing
    if text:
        run = p.add_run(text)
        if bold: run.bold = True
        if italic: run.italic = True
        if color: run.font.color.rgb = color
        if size: run.font.size = Pt(size)
    return p

def add_bullet(text, indent_cm=0.5):
    p = doc.add_paragraph(text, style='List Bullet')
    p.paragraph_format.left_indent = Cm(indent_cm + 0.5)
    p.paragraph_format.space_after = Pt(3)
    return p

def add_numbered(text):
    p = doc.add_paragraph(text, style='List Number')
    p.paragraph_format.left_indent = Cm(0.75)
    p.paragraph_format.space_after = Pt(3)
    return p

def add_callout(label, body, color_hex, text_color=SLATE_700):
    """Insert a single-cell colored callout box."""
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    cell = table.cell(0, 0)
    shade_cell(cell, color_hex)
    # cell margins
    tc_pr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for side, val in [('top', 180), ('left', 240), ('bottom', 180), ('right', 240)]:
        node = OxmlElement(f'w:{side}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tc_pr.append(tcMar)

    # label line
    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run(label)
    r.bold = True
    r.font.color.rgb = text_color
    r.font.size = Pt(10)
    if body:
        p2 = cell.add_paragraph(body)
        p2.paragraph_format.space_after = Pt(0)
        for run in p2.runs:
            run.font.color.rgb = text_color
            run.font.size = Pt(10)
    # spacer after
    doc.add_paragraph().paragraph_format.space_after = Pt(6)
    return table

def add_step(number, title, body):
    """Add a numbered step block (for how-to guides)."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(8)
    p.paragraph_format.space_after = Pt(2)
    r1 = p.add_run(f"Langkah {number}: ")
    r1.bold = True
    r1.font.color.rgb = NAVY
    r2 = p.add_run(title)
    r2.bold = True

    if body:
        for line in body if isinstance(body, list) else [body]:
            bp = doc.add_paragraph(line, style='List Bullet')
            bp.paragraph_format.left_indent = Cm(1.0)
            bp.paragraph_format.space_after = Pt(2)

def add_table_grid(headers, rows, col_widths_cm=None, header_bg=NAVY, header_color=WHITE):
    """Create a styled table with navy header."""
    table = doc.add_table(rows=1 + len(rows), cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = 'Light Grid Accent 1'

    # set widths
    if col_widths_cm:
        for i, w in enumerate(col_widths_cm):
            for cell in table.columns[i].cells:
                cell.width = Cm(w)

    # header
    hdr = table.rows[0]
    for i, h in enumerate(headers):
        cell = hdr.cells[i]
        shade_cell(cell, "%02X%02X%02X" % (header_bg[0], header_bg[1], header_bg[2]))
        cell.text = ''
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        r.bold = True
        r.font.color.rgb = header_color
        r.font.size = Pt(10)

    # rows
    for ridx, row in enumerate(rows):
        row_cells = table.rows[1 + ridx].cells
        for cidx, val in enumerate(row):
            cell = row_cells[cidx]
            cell.text = ''
            p = cell.paragraphs[0]
            r = p.add_run(str(val))
            r.font.size = Pt(10)
            r.font.color.rgb = SLATE_700
            if ridx % 2 == 1:
                shade_cell(cell, "F8FAFC")

    return table

def page_break():
    doc.add_page_break()

# ============================================================================
# COVER PAGE
# ============================================================================
# Spacer top
for _ in range(4):
    doc.add_paragraph()

# Brand badge
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("ONLINE ATTENDANCE SYSTEM")
r.font.size = Pt(11)
r.font.color.rgb = INDIGO
r.bold = True
r.font.name = 'Calibri'

# Spacer
doc.add_paragraph().paragraph_format.space_after = Pt(48)

# Title
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("Manual Book")
r.font.size = Pt(48)
r.font.bold = True
r.font.color.rgb = NAVY

# Subtitle
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(12)
r = p.add_run("Sistem Absensi Online Karyawan")
r.font.size = Pt(22)
r.font.color.rgb = SLATE_700

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(6)
r = p.add_run("Laravel 11 · SQLite · Tailwind · Alpine.js · Leaflet")
r.font.size = Pt(12)
r.font.color.rgb = SLATE_500
r.italic = True

# Spacer
doc.add_paragraph().paragraph_format.space_after = Pt(96)

# Metadata box
table = doc.add_table(rows=4, cols=2)
table.alignment = WD_TABLE_ALIGNMENT.CENTER
meta = [
    ("Versi", "1.0"),
    ("Tanggal", date.today().strftime("%d %B %Y")),
    ("Penulis", "Tim Pengembang"),
    ("Lisensi", "Internal Use Only"),
]
for i, (k, v) in enumerate(meta):
    c0 = table.cell(i, 0)
    c1 = table.cell(i, 1)
    c0.width = Cm(4)
    c1.width = Cm(8)
    shade_cell(c0, LIGHT_BG)
    p = c0.paragraphs[0]
    r = p.add_run(k)
    r.bold = True
    r.font.size = Pt(11)
    r.font.color.rgb = NAVY
    p2 = c1.paragraphs[0]
    r2 = p2.add_run(v)
    r2.font.size = Pt(11)
    r2.font.color.rgb = SLATE_700

page_break()

# ============================================================================
# DAFTAR ISI (placeholder — Word will auto-populate on open)
# ============================================================================
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.LEFT
r = p.add_run("Daftar Isi")
r.font.size = Pt(22)
r.font.bold = True
r.font.color.rgb = NAVY
r.font.name = 'Calibri'

add_paragraph("", space_after=12)

# TOC field code — Word populates this when user opens the document
p = doc.add_paragraph()
r = p.add_run()
fld_begin = OxmlElement('w:fldChar')
fld_begin.set(qn('w:fldCharType'), 'begin')
r._r.append(fld_begin)

r2 = p.add_run()
instr = OxmlElement('w:instrText')
instr.set(qn('xml:space'), 'preserve')
instr.text = ' TOC \\o "1-3" \\h \\z \\u '
r2._r.append(instr)

r3 = p.add_run()
fld_sep = OxmlElement('w:fldChar')
fld_sep.set(qn('w:fldCharType'), 'separate')
r3._r.append(fld_sep)

r4 = p.add_run("Tekan F9 di Microsoft Word, atau klik kanan → Update Field, untuk memperbarui daftar isi.")
r4.italic = True
r4.font.color.rgb = SLATE_500
r4.font.size = Pt(10)

r5 = p.add_run()
fld_end = OxmlElement('w:fldChar')
fld_end.set(qn('w:fldCharType'), 'end')
r5._r.append(fld_end)

page_break()

# ============================================================================
# CHAPTER 1 — PENDAHULUAN
# ============================================================================
h = doc.add_heading("1. Pendahuluan", level=1)

add_paragraph(
    "Online Attendance System adalah aplikasi web untuk mengelola absensi karyawan secara digital. "
    "Sistem ini menggantikan pencatatan absensi manual dengan fitur modern seperti verifikasi lokasi (geofencing), "
    "foto selfie saat absen, dan pelaporan real-time. Karyawan dapat melakukan absen masuk/pulang dari smartphone "
    "atau laptop mereka, sementara Admin/HRD dan Kepala Divisi memiliki dashboard masing-masing untuk monitoring."
)

add_heading = lambda text, level=2: doc.add_heading(text, level=level)

add_heading("1.1 Fitur Utama", level=2)
features = [
    ("Absen dengan Foto & Lokasi", "Foto selfie wajib + verifikasi GPS dalam radius kantor (default 200m)."),
    ("Tukar Shift Langsung", "Karyawan bisa ajukan tukar shift; begitu target ACC, jadwal otomatis tertukar."),
    ("Tukar Hari Libur", "Pindah tanggal libur ke tanggal kerja tertentu, apply langsung saat pengajuan dibuat."),
    ("Multi-User Schedule", "Admin bisa input jadwal untuk banyak karyawan sekaligus (satu tanggal + shift, banyak user)."),
    ("Hari Libur Nasional", "Admin input hari libur → jadwal karyawan auto-ke-mark libur + notifikasi broadcast."),
    ("Laporan Bulanan", "Summary card + daily calendar grid, export ke CSV."),
    ("Audit Log", "Track login, clock-in/out, dan perubahan penting dengan filter action + user."),
    ("Notifikasi In-App + Email", "Bell icon dengan unread badge + email ke karyawan untuk swap / approval / reset pwd."),
    ("Reset Password", "Self-service via email link, expired 60 menit."),
    ("Multi-Role Dashboard", "3 role berbeda: Admin/HRD, Kepala Divisi, Karyawan — masing-masing scoped ke area-nya."),
]
for title, desc in features:
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    r1 = p.add_run(f"• {title}: ")
    r1.bold = True
    r1.font.color.rgb = NAVY
    p.add_run(desc)

add_heading("1.2 Tech Stack", level=2)
add_table_grid(
    headers=["Komponen", "Teknologi"],
    rows=[
        ["Backend",        "Laravel 11 (PHP 8.2)"],
        ["Database",       "SQLite (file: database/database.sqlite)"],
        ["Frontend",       "Blade + Tailwind CSS (CDN) + Alpine.js"],
        ["Map",            "Leaflet 1.9.4 + OpenStreetMap"],
        ["Auth",           "Custom session-based (PHP password_hash)"],
        ["Notifications",  "In-app table + email via Laravel Mail"],
    ],
    col_widths_cm=[4, 12]
)

add_heading("1.3 User Roles", level=2)
add_table_grid(
    headers=["Role", "Hak Akses"],
    rows=[
        ["Admin / HRD",
         "Kelola data master (karyawan, divisi, shift, kantor), input jadwal bulk, hari libur, monitoring semua karyawan, "
         "lihat audit log, laporan bulanan, reset password user."],
        ["Kepala Divisi",
         "Lihat absensi anggota divisinya saja (scoped, auto-filter by division_id). Tidak ada approval workflow lagi — "
         "tukar shift/hari libur auto-approve begitu target ACC."],
        ["Karyawan",
         "Login, clock-in/out, lihat jadwal sendiri, lihat riwayat absensi, ajukan & ACC tukar shift, "
         "ajukan tukar hari libur, terima notifikasi, reset password sendiri."],
    ],
    col_widths_cm=[4, 12]
)

page_break()

# ============================================================================
# CHAPTER 2 — LOGIN & SETUP AWAL
# ============================================================================
doc.add_heading("2. Cara Login & Setup Awal", level=1)

doc.add_heading("2.1 Login Pertama Kali", level=2)
add_paragraph(
    "Semua akun demo sudah di-seed dengan password default. Gunakan salah satu akun berikut untuk login pertama kali:"
)
add_table_grid(
    headers=["Email", "Password", "Role"],
    rows=[
        ["admin@attendance.test",       "password123", "Admin / HRD"],
        ["budi.kadiv@attendance.test",   "password123", "KD IT"],
        ["siti.kadiv@attendance.test",   "password123", "KD Finance"],
        ["andi@attendance.test",        "password123", "Karyawan IT"],
        ["hendra@attendance.test",      "password123", "Karyawan IT"],
        ["citra@attendance.test",       "password123", "Karyawan IT"],
        ["eko@attendance.test",         "password123", "Karyawan Finance"],
    ],
    col_widths_cm=[6, 4, 6]
)

add_callout(
    "Penting",
    "Password demo ini hanya untuk environment development. Pada production, "
    "admin harus melakukan reset password untuk semua akun setelah deploy.",
    "FEF3C7"
)

doc.add_heading("2.2 Langkah Login", level=2)
add_step(1, "Buka halaman login", [
    "Navigasi ke URL aplikasi (default: http://127.0.0.1:8000).",
    "Otomatis redirect ke /login kalau belum authenticated.",
])
add_step(2, "Isi kredensial", [
    "Field Email: masukkan alamat email akun (mis. andi@attendance.test).",
    "Field Password: masukkan password default 'password123'.",
    "(Optional) Centang 'Remember me' untuk session lebih lama.",
])
add_step(3, "Submit", [
    "Klik tombol 'Masuk'. Sistem akan redirect ke dashboard jika berhasil.",
    "Kalau gagal, error 'Email atau password salah' akan muncul di atas form.",
])

add_callout(
    "Lupa password?",
    "Klik link 'Lupa password?' di bawah tombol login. Masukkan email Anda, lalu cek inbox (atau storage/logs/laravel.log "
    "di mode development) untuk link reset. Link berlaku 60 menit.",
    "E0E7FF"
)

page_break()

# ============================================================================
# CHAPTER 3 — PANDUAN KARYAWAN
# ============================================================================
doc.add_heading("3. Panduan Karyawan", level=1)
add_paragraph(
    "Bagian ini menjelaskan semua fitur yang tersedia untuk role Karyawan. Login sebagai salah satu akun karyawan "
    "(mis. andi@attendance.test) untuk mengikuti panduan."
)

# 3.1 Dashboard
doc.add_heading("3.1 Dashboard Karyawan", level=2)
add_paragraph(
    "Setelah login, karyawan melihat dashboard dengan 3 section utama:"
)
add_table_grid(
    headers=["Section", "Isi"],
    rows=[
        ["Hero Card Hari Ini",
         "Menampilkan shift hari ini (Pagi/Siang/Malam + jam kerja), tombol Absen Masuk/Pulang (atau Lihat Absensi "
         "kalau sudah lengkap), dan status badge (Selesai/Sedang Bekerja/Belum Absen/Hari Libur)."],
        ["Quick Actions",
         "4 kartu: Tukar Shift, Tukar Libur, Riwayat (ke daftar absensi), Status (ke status pengajuan)."],
        ["Status Pengajuan",
         "Daftar 5 tukar shift + 5 tukar hari libur terbaru yang melibatkan Anda, dengan badge status (Pending / "
         "Approved / Rejected / Cancelled)."],
        ["Jadwal Berikutnya & Riwayat Absensi",
         "7 hari jadwal ke depan + 7 hari riwayat absensi terakhir, masing-masing dengan badge libur/kerja."],
    ],
    col_widths_cm=[4, 12]
)

# 3.2 Absensi (Clock In / Out)
doc.add_heading("3.2 Absensi (Clock In / Out)", level=2)

doc.add_heading("3.2.1 Clock In", level=3)
add_step(1, "Dari Dashboard", [
    "Klik tombol 'Absen Masuk' di hero card (warna brand biru).",
    "Sistem redirect ke halaman absensi.",
])
add_step(2, "Ambil foto selfie", [
    "Klik 'Buka Kamera' — browser akan minta permission akses kamera.",
    "Posisikan wajah di tengah frame guide yang muncul.",
    "Klik tombol shutter (lingkaran putih di bawah preview).",
    "Setelah foto diambil, preview akan tampil dengan tombol 'Ambil Ulang' / 'Ganti Kamera' / 'Lanjut'.",
])
add_step(3, "Verifikasi lokasi", [
    "Browser akan minta permission akses lokasi (GPS).",
    "Sistem otomatis deteksi koordinat Anda (lat, lng).",
    "Jarak ke kantor pusat dihitung menggunakan formula Haversine.",
])
add_step(4, "Submit", [
    "Klik 'Kirim Absen' — sistem akan menyimpan attendance dengan timestamp + foto + lokasi.",
    "Kalau di LUAR radius kantor (default 200m), sistem akan tolak dengan pesan error.",
])
add_callout(
    "Demo Mode",
    "Kalau GPS tidak tersedia (mis. di emulator/laptop tanpa GPS), centang checkbox 'Demo Mode' sebelum submit. "
    "Sistem akan bypass validasi radius. Attendance tetap tersimpan dengan foto, tapi tanpa koordinat lokasi.",
    "FEF3C7"
)

doc.add_heading("3.2.2 Clock Out", level=3)
add_paragraph(
    "Proses sama seperti Clock In. Setelah klik 'Absen Pulang' dari dashboard, ikuti langkah-langkah yang sama. "
    "Field foto & lokasi juga wajib."
)

doc.add_heading("3.2.3 Aturan & Validasi", level=2)
add_table_grid(
    headers=["Situasi", "Respons Sistem"],
    rows=[
        ["Belum ada jadwal kerja hari ini (libur)", "Tolak: 'Hari ini adalah hari libur. Tidak bisa melakukan absen.'"],
        ["Sudah clock-in, double clock-in", "Tolak: 'Anda sudah melakukan absensi masuk hari ini.'"],
        ["Sudah clock-out, double clock-out", "Tolak: 'Anda sudah melakukan clock-out hari ini.'"],
        ["Lokasi di luar radius (>200m)", "Tolak: 'Anda di luar jangkauan kantor (X meter dari titik absen, maksimal 200 meter).'"],
        ["Tidak izinkan kamera / GPS", "Tolak: 'Camera/GPS permission required.'"],
        ["Demo Mode ON", "Bypass radius check, tetap simpan attendance tanpa koordinat."],
    ],
    col_widths_cm=[5, 11]
)

# 3.3 Tukar Shift
doc.add_heading("3.3 Tukar Shift", level=2)
add_paragraph(
    "Karyawan bisa mengajukan tukar shift dengan karyawan satu divisi yang jadwalnya BEDA SHIFT di tanggal yang sama. "
    "Begitu target ACC, shift otomatis tertukar (tidak perlu approval KD)."
)

doc.add_heading("Alur Tukar Shift", level=3)
add_step(1, "Buka halaman Tukar Shift", [
    "Dari dashboard, klik kartu 'Tukar Shift' ATAU dari top nav 'Tukar Shift'.",
])
add_step(2, "Buat pengajuan", [
    "Pilih tanggal yang ingin ditukar.",
    "Pilih target karyawan (dropdown hanya menampilkan karyawan satu divisi Anda).",
    "Pastikan Anda berdua punya jadwal kerja di tanggal tsb dengan shift yang BERBEDA — kalau sama, sistem tolak.",
    "Tulis alasan (wajib).",
    "Klik 'Kirim Pengajuan'. Status: Pending Target.",
])
add_step(3, "Target ACC", [
    "Target menerima notifikasi (bell icon + email).",
    "Buka halaman detail, klik 'Setujui' atau 'Tolak' dengan catatan.",
    "Setelah target ACC → shift LANGSUNG tertukar + status: Approved.",
])
add_callout(
    "Catatan Penting",
    "Kalau ada request aktif di tanggal yang sama (status Pending atau Approved), pengajuan baru ditolak. "
    "Status pengajuan hanya visible di halaman 'Status Pengajuan' di dashboard atau di halaman detail masing-masing.",
    "E0E7FF"
)

# 3.4 Tukar Libur
doc.add_heading("3.4 Tukar Hari Libur", level=2)
add_paragraph(
    "Pindahkan hari libur Anda ke tanggal lain. Tidak ada approval gate — begitu dibuat, jadwal langsung ter-update."
)

doc.add_heading("Alur Tukar Libur", level=3)
add_step(1, "Buka halaman Tukar Libur", [
    "Dari dashboard, klik kartu 'Tukar Libur' atau top nav.",
])
add_step(2, "Isi form", [
    "Pilih 'Libur Lama' = tanggal Anda yang saat ini libur (biasanya weekend).",
    "Pilih 'Libur Baru' = tanggal yang ingin dijadikan libur (biasanya workday Anda).",
    "Tulis alasan (wajib).",
    "Klik 'Kirim Pengajuan'.",
])
add_step(3, "Otomatis selesai", [
    "Sistem langsung update schedule: libur lama jadi kerja, tanggal baru jadi libur.",
    "Status: Approved. Tidak ada notifikasi tambahan (instant).",
])

# 3.5 Notifikasi
doc.add_heading("3.5 Notifikasi", level=2)
add_paragraph(
    "Bell icon di top-right menampilkan unread notifications. Klik untuk membuka dropdown. Setiap notifikasi "
    "memiliki:"
)
add_table_grid(
    headers=["Elemen", "Fungsi"],
    rows=[
        ["Title", "Judul singkat (mis. 'Tukar Shift Disetujui')"],
        ["Body",   "Pesan detail dengan info relevan (tanggal, nama partner, dll)"],
        ["Action URL", "Klik untuk navigate ke halaman terkait (mis. ke detail swap)"],
        ["Unread badge", "Angka merah di pojok bell icon — klik 'Tandai semua dibaca' untuk reset"],
    ],
    col_widths_cm=[4, 12]
)
add_paragraph(
    "5 notifikasi terakhir juga ditampilkan di dropdown. Untuk lihat semua, klik 'Lihat semua notifikasi' "
    "di bagian bawah dropdown → ke /notifications.",
    italic=True
)

# 3.6 Profile & Reset Password
doc.add_heading("3.6 Profile & Reset Password", level=2)
add_step(1, "Akses profile", [
    "Klik avatar/nama Anda di top-right → muncul menu dropdown.",
    "Pilih 'Profil' untuk lihat data diri (read-only).",
])
add_step(2, "Reset password sendiri", [
    "Dari menu profile, klik 'Ubah Password' ATAU buka halaman /forgot-password.",
    "Masukkan email Anda → sistem kirim link reset ke email.",
    "Buka link dalam 60 menit → masukkan password baru (min 8 char).",
])

page_break()

# ============================================================================
# CHAPTER 4 — PANDUAN KEPALA DIVISI
# ============================================================================
doc.add_heading("4. Panduan Kepala Divisi (KD)", level=1)
add_paragraph(
    "KD memiliki akses terbatas: hanya bisa lihat absensi anggota divisinya sendiri (auto-scoped oleh sistem). "
    "Tidak ada halaman approval lagi — swap requests auto-approve begitu target ACC."
)

doc.add_heading("4.1 Login & Dashboard", level=2)
add_paragraph(
    "Login dengan akun KD (mis. budi.kadiv@attendance.test untuk KD IT). Dashboard KD sama dengan karyawan — "
    "tapi menu navigasi berbeda: hanya ada 'Absensi Divisi' (tidak ada menu Tukar Shift/Tukar Libur/Approvals)."
)

doc.add_heading("4.2 Absensi Divisi", level=2)
add_paragraph(
    "Halaman dedicated: /kadiv/attendances. Menampilkan daftar absensi SELURUH anggota divisi Anda "
    "dengan fitur lengkap:"
)
add_table_grid(
    headers=["Fitur", "Deskripsi"],
    rows=[
        ["Header Title", "Menampilkan 'Absensi Divisi {Nama Divisi}' — eksplisit menunjukkan scope"],
        ["Summary Cards", "5 cards: Jumlah Anggota, Total Record, Sudah Clock-In, Belum Clock-Out, Belum Clock-In"],
        ["Filter", "Tanggal (dari-sampai), per anggota (dropdown), per status"],
        ["Tabel", "Sama dengan admin: kolom Tanggal, Nama Karyawan, Clock In, Clock Out, Lokasi, tombol Detail"],
        ["Pagination", "25 record per halaman, dengan query string preserved"],
    ],
    col_widths_cm=[4, 12]
)

doc.add_heading("4.3 Hard Guard — Cross-Division Access", level=2)
add_callout(
    "PENTING — Pembatasan Akses",
    "KD TIDAK BISA melihat absensi karyawan dari divisi lain, meskipun URL di-tweak manual. "
    "Sistem akan redirect ke halaman 403 dengan pesan 'Absensi ini bukan dari anggota divisi Anda.'",
    "FEE2E2"
)

doc.add_heading("4.4 Detail Absensi", level=2)
add_paragraph(
    "Klik tombol 'Detail' di tabel → halaman detail menampilkan:"
)
add_bullet("Header: nama karyawan + divisi + role + shift info hari itu")
add_bullet("Card Clock In: waktu, foto selfie (klik untuk full-size), badge radius (Dalam/Luar {{jarak}}m), peta Leaflet")
add_bullet("Card Clock Out: sama seperti Clock In")
add_bullet("Peta menampilkan marker absen (hijau untuk in, indigo untuk out), marker kantor pusat, dan lingkaran radius kantor")

doc.add_heading("4.5 Apa yang TIDAK Bisa Dilakukan KD", level=2)
add_table_grid(
    headers=["Aksi", "Status"],
    rows=[
        ["Approve/Reject tukar shift", "Tidak ada lagi — auto-approve saat target ACC"],
        ["Approve/Reject tukar hari libur", "Tidak ada lagi — auto-approve saat karyawan create"],
        ["Lihat absensi divisi lain",         "Ditolak 403 oleh hard guard"],
        ["Kelola jadwal/karyawan",           "Tidak ada akses (Admin only)"],
        ["Reset password user",                "Tidak ada akses"],
    ],
    col_widths_cm=[6, 10]
)

page_break()

# ============================================================================
# CHAPTER 5 — PANDUAN ADMIN/HRD
# ============================================================================
doc.add_heading("5. Panduan Admin / HRD", level=1)
add_paragraph(
    "Admin/HRD memiliki akses penuh ke seluruh sistem. Halaman admin ada di bawah prefix /admin/* di URL. "
    "Login dengan admin@attendance.test untuk panduan ini."
)

# 5.1 Dashboard
doc.add_heading("5.1 Dashboard Admin", level=2)
add_paragraph(
    "Sama dengan karyawan — hero card absensi, quick actions, dan status pengajuan. Tapi nav tambahan tersedia: "
    "dropdown 'Admin' di top nav membuka sub-menu lengkap."
)

# 5.2 Setup Awal
doc.add_heading("5.2 Setup Awal Sistem", level=2)

doc.add_heading("5.2.1 Tambah Karyawan Baru", level=3)
add_step(1, "Buka menu Admin → Karyawan", [
    "Top nav → klik dropdown 'Admin' → 'Karyawan'.",
    "Halaman /admin/employees/index menampilkan daftar semua user.",
])
add_step(2, "Klik 'Tambah Karyawan'", [
    "Isi NIK (nomor induk), nama lengkap, email, password default.",
    "Pilih divisi dari dropdown.",
    "Pilih role (karyawan / kepala_divisi / admin).",
    "Pilih position (opsional, mis. 'Backend Developer').",
    "Submit.",
])

doc.add_heading("5.2.2 Setup Shift", level=3)
add_step(1, "Menu Admin → Shift", [
    "Lihat 3 shift default: Pagi (08:00-16:00), Siang (16:00-00:00), Malam (00:00-08:00).",
])
add_step(2, "Tambah shift custom (optional)", [
    "Klik 'Tambah Shift'. Isi nama, jam mulai, jam selesai.",
    "Submit. Shift baru langsung tersedia di form jadwal & tukar shift.",
])

doc.add_heading("5.2.3 Setup Kantor Pusat", level=3)
add_step(1, "Menu Admin → Lokasi Kantor", [
    "Halaman /admin/office-locations menampilkan 1 record kantor pusat (singleton).",
])
add_step(2, "Edit koordinat", [
    "Klik 'Edit' → muncul Leaflet map dengan marker di posisi kantor.",
    "Drag marker untuk pindah posisi, atau klik 'Lokasi Saya' untuk gunakan GPS saat ini.",
    "Search box (Nominatim/OpenStreetMap) untuk cari alamat → map auto-zoom ke lokasi.",
    "Atur radius geofencing dalam meter (default 200m).",
    "Submit.",
])

doc.add_heading("5.2.4 Input Jadwal (Bulk)", level=3)
add_paragraph(
    "Admin bisa input jadwal untuk banyak karyawan sekaligus — pilih tanggal + karyawan (multiple) + shift, klik apply."
)
add_step(1, "Menu Admin → Jadwal Kerja", [
    "Halaman /admin/schedules/index menampilkan grid 14 hari ke depan × semua karyawan.",
])
add_step(2, "Klik 'Tambah Jadwal'", [
    "Pilih tanggal (datepicker).",
    "Pilih shift dari dropdown (wajib) ATAU pilih 'Hari Libur' (shift auto-null).",
    "Pilih divisi (optional, untuk filter karyawan).",
    "Centang karyawan yang ingin diberi jadwal (ada tombol 'Pilih Semua' + 'Kosongkan').",
    "Submit. Sistem akan skip karyawan yang sudah ada jadwal di tanggal tsb (tidak overwrite).",
])
add_callout(
    "Behavior Penting",
    "Submit dengan 5 karyawan, 1 di antaranya sudah punya jadwal hari itu → flash message: "
    "'Jadwal berhasil ditambahkan untuk 4 karyawan. Lewati 1 (sudah ada jadwal): Nama Karyawan.'",
    "E0E7FF"
)

doc.add_heading("5.2.5 Edit/Hapus Jadwal", level=3)
add_paragraph(
    "Klik cell jadwal di grid → modal edit. Atau klik ikon tempat sampah untuk hapus. "
    "Schedule bisa diedit/dihapus individual."
)

# 5.3 Hari Libur
doc.add_heading("5.3 Hari Libur Nasional", level=2)
add_step(1, "Menu Admin → Hari Libur", [
    "Halaman /admin/holidays menampilkan daftar hari libur yang sudah di-set.",
])
add_step(2, "Tambah Hari Libur", [
    "Klik 'Tambah Hari Libur'. Isi tanggal dan nama (mis. 'Hari Kemerdekaan').",
    "Centang 'Hari Libur Nasional' kalau applicable.",
    "Submit. Sistem otomatis update SEMUA schedule di tanggal tsb → status=libur, shift=nullified.",
])
add_step(3, "Broadcast notification", [
    "Setiap karyawan dapat notifikasi in-app + email: 'Hari Libur Baru: {nama}'.",
])

# 5.4 Laporan Bulanan
doc.add_heading("5.4 Laporan Bulanan", level=2)
add_step(1, "Buka Laporan", [
    "Menu Admin → Laporan Bulanan, atau URL /admin/reports/monthly.",
])
add_step(2, "Pilih bulan", [
    "Query string ?month=YYYY-MM (default: bulan ini).",
    "Halaman menampilkan summary cards: Total Hari Kerja, Hadir, Terlambat, Izin, Alfa.",
])
add_step(3, "Calendar grid", [
    "Grid harian per karyawan: hijau=hadir, kuning=terlambat, merah=alfa, abu=libur.",
])
add_step(4, "Export CSV", [
    "Klik tombol 'Export CSV' → download file .csv dengan data absensi bulan tsb.",
])

# 5.5 Audit Log
doc.add_heading("5.5 Audit Log", level=2)
add_paragraph(
    "Menu Admin → Audit Log menampilkan semua aksi penting yang terjadi di sistem:"
)
add_table_grid(
    headers=["Action", "Kapan Tercatat"],
    rows=[
        ["login", "User berhasil login"],
        ["logout", "User logout"],
        ["shift_swap_created", "Karyawan buat pengajuan tukar shift"],
        ["shift_swap_target_accepted", "Target ACC pengajuan → auto-approved"],
        ["shift_swap_auto_approved", "Pengajuan shift swap auto-approved"],
        ["dayoff_swap_auto_approved", "Pengajuan tukar libur auto-approved"],
        ["dayoff_swap_created", "Karyawan buat pengajuan tukar libur (legacy)"],
        ["clock_in / clock_out", "Catatan absen"],
    ],
    col_widths_cm=[6, 10]
)
add_paragraph(
    "Setiap entry mencatat: user_id, action, resource_type/id, IP address, user agent, dan metadata JSON. "
    "Filter berdasarkan action atau user tersedia."
)

# 5.6 Reset Password User
doc.add_heading("5.6 Reset Password User", level=2)
add_step(1, "Menu Admin → Karyawan", [
    "Buka /admin/employees/index.",
])
add_step(2, "Klik 'Reset Password' di baris user", [
    "Sistem generate token dan kirim link reset ke email user (atau tampilkan di flash untuk testing).",
    "User klik link, masukkan password baru. Password user langsung ter-update.",
])
add_callout(
    "Info",
    "Token reset expired dalam 60 menit. Setelah dipakai, token langsung dihapus dari DB.",
    "E0E7FF"
)

page_break()

# ============================================================================
# CHAPTER 6 — FAQ & TROUBLESHOOTING
# ============================================================================
doc.add_heading("6. FAQ & Troubleshooting", level=1)

doc.add_heading("6.1 Pertanyaan Umum (FAQ)", level=2)

doc.add_heading("Q: Login gagal, 'Email atau password salah'", level=3)
add_paragraph(
    "Pastikan:"
)
add_bullet("Email di-input benar (case-insensitive)")
add_bullet("Password benar — default 'password123' untuk semua akun demo")
add_bullet("Akun belum di-hapus dari DB")
add_paragraph(
    "Kalau masih gagal, coba reset password lewat halaman /forgot-password, atau hubungi Admin untuk force-reset.",
    italic=True
)

doc.add_heading("Q: Kamera / GPS tidak berfungsi", level=3)
add_paragraph("Solusi:")
add_bullet("Browser minta permission — klik 'Allow' di popup permission bar")
add_bullet("Pastikan pakai HTTPS (atau localhost) — kamera butuh secure context")
add_bullet("Cek setting browser: chrome://settings/content/camera dan chrome://settings/content/location")
add_bullet("Di HP, pastikan GPS aktif dan browser diizinkan akses lokasi")
add_bullet("Sebagai alternatif, centang 'Demo Mode' untuk bypass (foto tetap disimpan, lokasi kosong)")

doc.add_heading("Q: Bagaimana cara cek siapa yang belum clock-out", level=3)
add_paragraph(
    "Buka halaman Absensi (karyawan → Riwayat / KD → Absensi Divisi / Admin → Daftar Absensi). "
    "Filter dengan 'Belum Clock-Out' — semua record dengan clock_in tapi tanpa clock_out akan tampil."
)

doc.add_heading("Q: Tukar shift saya stuck di 'Pending Target' lama", level=3)
add_paragraph(
    "Target belum merespons. Beri tahu target untuk ACC / reject via menu 'Status Pengajuan' di dashboard-nya, "
    "atau lewat halaman detail pengajuan (jika dia requester kebetulan sama)."
)

doc.add_heading("Q: Lupa password admin", level=3)
add_paragraph(
    "Ada 2 cara:"
)
add_numbered("Reset via /forgot-password (asalkan email masih bisa diakses)")
add_numbered("Force update via CLI: buka tinker, jalankan:")
p = doc.add_paragraph()
r = p.add_run("   php artisan tinker --execute=\"\\App\\Models\\User::where('email','admin@attendance.test')->first()->update(['password'=>'newPassword']);\"")
r.font.name = 'Consolas'
r.font.size = Pt(9)
r.font.color.rgb = SLATE_700

doc.add_heading("6.2 Troubleshooting Lanjutan", level=2)

doc.add_heading("Error: '419 Page Expired' saat submit form", level=3)
add_paragraph(
    "Session CSRF expired (default 2 jam). Refresh halaman dan submit ulang. Untuk extend, edit config/session.php lifetime."
)

doc.add_heading("Error: 'SQLSTATE[23000]: Integrity constraint violation'", level=3)
add_paragraph(
    "Mencoba insert schedule duplikat (user_id + date). Biasanya karena ada 2 request bersamaan, atau cleanup test gagal. "
    "Cek DB: "
)
p = doc.add_paragraph()
r = p.add_run("   SELECT * FROM schedules WHERE user_id=X AND date='YYYY-MM-DD';")
r.font.name = 'Consolas'
r.font.size = Pt(9)
r.font.color.rgb = SLATE_700

doc.add_heading("Leaflet map blank / tidak muncul", level=3)
add_bullet("Cek koneksi internet (Leaflet butuh OSM tile server)")
add_bullet("Cek console browser untuk error CORS / 404")
add_bullet("Pastikan container punya tinggi eksplisit (h-64 sudah di-set di view)")

doc.add_heading("Email tidak terkirim", level=3)
add_paragraph(
    "Di mode development, default mail driver adalah 'log' — email ditulis ke storage/logs/laravel.log, "
    "BUKAN terkirim ke inbox asli. Untuk kirim real, edit .env:"
)
p = doc.add_paragraph()
r = p.add_run("   MAIL_MAILER=smtp\n   MAIL_HOST=smtp.gmail.com\n   MAIL_PORT=587\n   MAIL_USERNAME=...\n   MAIL_PASSWORD=...\n   MAIL_ENCRYPTION=tls\n   MAIL_FROM_ADDRESS=attendance@perusahaan.com")
r.font.name = 'Consolas'
r.font.size = Pt(9)
r.font.color.rgb = SLATE_700

page_break()

# ============================================================================
# APPENDIX
# ============================================================================
doc.add_heading("Appendix A. Struktur Folder", level=1)
add_paragraph("Berikut struktur direktori utama project:")
p = doc.add_paragraph()
r = p.add_run(
    "attendance-app/\n"
    "├── app/\n"
    "│   ├── Http/Controllers/         (semua controller, dipisah Admin/, Auth/, Kadiv/)\n"
    "│   ├── Models/                   (Eloquent models)\n"
    "│   ├── Services/                 (NotificationService, dll)\n"
    "│   └── Middleware/              (RoleMiddleware, dll)\n"
    "├── database/\n"
    "│   ├── migrations/               (schema definitions)\n"
    "│   └── seeders/                  (DatabaseSeeder)\n"
    "├── resources/\n"
    "│   └── views/                    (Blade templates)\n"
    "├── routes/\n"
    "│   └── web.php                   (semua route definitions)\n"
    "├── public/                       (entry point + uploads)\n"
    "├── storage/\n"
    "│   └── app/public/               (uploaded photos)\n"
    "└── .env                          (environment config)"
)
r.font.name = 'Consolas'
r.font.size = Pt(9)
r.font.color.rgb = SLATE_700

doc.add_heading("Appendix B. Default Demo Accounts", level=1)
add_table_grid(
    headers=["Email", "Password", "Role", "Divisi"],
    rows=[
        ["admin@attendance.test",      "password123", "admin",        "HRD"],
        ["budi.kadiv@attendance.test",  "password123", "kepala_divisi", "IT"],
        ["siti.kadiv@attendance.test",  "password123", "kepala_divisi", "Finance"],
        ["andi@attendance.test",       "password123", "karyawan",     "IT"],
        ["citra@attendance.test",      "password123", "karyawan",     "IT"],
        ["dedi@attendance.test",       "password123", "karyawan",     "IT"],
        ["hendra@attendance.test",     "password123", "karyawan",     "IT"],
        ["eko@attendance.test",        "password123", "karyawan",     "Finance"],
        ["fitri@attendance.test",      "password123", "karyawan",     "Finance"],
        ["gita@attendance.test",       "password123", "karyawan",     "HRD"],
    ],
    col_widths_cm=[6, 4, 4, 4]
)

doc.add_heading("Appendix C. URL Reference", level=1)
add_table_grid(
    headers=["URL Pattern", "Role", "Halaman"],
    rows=[
        ["/login",                            "Semua",         "Halaman login"],
        ["/forgot-password",                  "Semua",         "Form lupa password"],
        ["/reset-password/{token}",           "Semua",         "Form reset password (via email link)"],
        ["/dashboard",                        "Authenticated", "Dashboard sesuai role"],
        ["/attendance",                       "Karyawan",      "Halaman absensi (clock-in/out)"],
        ["/shift-swaps",                      "Karyawan",      "Daftar tukar shift"],
        ["/shift-swaps/create",               "Karyawan",      "Form buat tukar shift"],
        ["/shift-swaps/{id}",                 "Karyawan",      "Detail & ACC pengajuan"],
        ["/day-off-swaps",                    "Karyawan",      "Daftar tukar hari libur"],
        ["/day-off-swaps/create",             "Karyawan",      "Form tukar libur"],
        ["/notifications",                    "Authenticated", "Semua notifikasi"],
        ["/kadiv/attendances",                "KD",            "Daftar absensi divisi"],
        ["/kadiv/attendances/{id}",           "KD",            "Detail absensi anggota divisi"],
        ["/admin/attendances",                "Admin",         "Daftar semua absensi"],
        ["/admin/attendances/{id}",           "Admin",         "Detail absensi"],
        ["/admin/employees",                  "Admin",         "Kelola karyawan"],
        ["/admin/schedules",                  "Admin",         "Kelola jadwal"],
        ["/admin/schedules/create",           "Admin",         "Form jadwal (multi-user)"],
        ["/admin/shifts",                     "Admin",         "Kelola shift"],
        ["/admin/divisions",                  "Admin",         "Kelola divisi"],
        ["/admin/holidays",                   "Admin",         "Kelola hari libur"],
        ["/admin/office-locations",           "Admin",         "Lokasi kantor (Leaflet)"],
        ["/admin/reports/monthly",            "Admin",         "Laporan bulanan"],
        ["/admin/audit-logs",                 "Admin",         "Audit log viewer"],
    ],
    col_widths_cm=[6, 3, 7]
)

doc.add_heading("Appendix D. Running Tests", level=1)
add_paragraph("Test files ada di project root (satu level di atas attendance-app/):")
add_table_grid(
    headers=["File Test", "Cakupan"],
    rows=[
        ["test-business-logic.php",        "Semua 10 fitur PDF + data master"],
        ["test-attendance.php",            "Clock-in/out, geofencing, demo mode"],
        ["test-tier1.php",                 "Notifikasi, audit log, password reset, holiday, monthly report"],
        ["test-multi-user-schedule.php",   "Bulk input jadwal + conflict skip"],
        ["test-admin-attendance.php",      "Admin attendance list + detail dengan Leaflet"],
        ["test-login-and-swap.php",        "Login flow + auto-approve tukar shift"],
        ["test-status-badge.php",          "Reusable status badge component"],
        ["test-kadiv-attendance.php",      "KD attendance scoped + 403 cross-division"],
    ],
    col_widths_cm=[6, 10]
)
add_paragraph("Jalankan semua test:")
p = doc.add_paragraph()
r = p.add_run("   for f in test-*.php; do php \"$f\"; done")
r.font.name = 'Consolas'
r.font.size = Pt(9)
r.font.color.rgb = SLATE_700

# ============================================================================
# SAVE
# ============================================================================
output_path = r"C:\Users\bangn\Documents\Kerja\online_attendance_system\MANUAL_BOOK_Online_Attendance_System.docx"
doc.save(output_path)
print(f"[OK] Saved: {output_path}")
print(f"  Total paragraphs: {len(doc.paragraphs)}")
print(f"  Total tables: {len(doc.tables)}")
