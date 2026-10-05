# Panduan Backend

## Cara kerja serah-terima

1. Semua controller membaca data lewat `App\Contracts\ClinicRepository`.
2. Saat ini terikat ke `MockClinicRepository` (data contoh) di `ClinicServiceProvider`.
3. Buat `EloquentClinicRepository` yang mengembalikan array dengan **kunci yang sama** lalu ganti binding. View tidak perlu diubah.
4. Aksi tulis (simpan, ubah, hapus) ada di controller dan ditandai komentar `// BACKEND:`. Validasi sudah ada.
5. Autentikasi saat ini memakai session sederhana (`session('staff')`, `session('patient')`). Ganti isi
   `StaffAuthController::login` dan `PortalController::login` dengan `Auth::attempt`, lalu sesuaikan `StaffRole`.
   Hapus `staff_users` di `config/clinic.php` dan kotak "Akun demo" di `auth/staff-login.blade.php`.

## Alur booking pasien

```
Pasien: Booking Online (keluhan wajib) -> status "Menunggu Persetujuan"
Dokter: Persetujuan Janji Temu -> Disetujui (jadi "Aktif" di tiket pasien) / Ditolak
Admin: Check-in Pasien pada hari kunjungan -> masuk antrean dokter
Pasien: Beranda menampilkan nomor sedang dilayani + sisa antrean
```

## Alur dokter ke apoteker

```
Dokter: Pemeriksaan -> simpan -> Resep (POST doctor.prescriptions.store)
        status resep = Menunggu
Apoteker: Resep Obat (GET pharmacist.prescriptions) melihat resep Menunggu
        -> Diproses -> Siap -> Diserahkan (PATCH pharmacist.prescriptions.update)
        saat Diserahkan: kurangi stok (Persediaan)
Dokter dan Pasien (Resep Saya): status terbaru dari apoteker ditampilkan, stepper 4 tahap
```

## Route dan tugas

| Peran | Method | URL | Name | Tugas backend |
|---|---|---|---|---|
| Pasien | POST | /masuk, /daftar | portal.login.submit, portal.register.submit | auth NIK + buat akun |
| Pasien | POST | /booking | booking.store | buat booking, nomor antrean harian, cek 1 tiket aktif per poli |
| Pasien | DELETE | /tiket/{code} | tickets.cancel | batalkan booking (status Aktif / Menunggu Persetujuan) |
| Pasien | GET | /beranda | portal.home | tiket aktif + status antrean langsung (`queueStatus`), resep dan kunjungan terakhir |
| Pasien | GET | /riwayat-medis, /resep-saya | portal.records, portal.prescriptions | baca-saja: pemeriksaan dan resep milik pasien (status resep dari apoteker) |
| Pasien | PUT | /profil | portal.profile.update | update telepon, alamat, golongan darah, alergi, kontak darurat, pembayaran |
| Pasien | PUT | /keamanan-akun | portal.password.update | ganti password pasien |
| Semua staf | PUT | /staff/keamanan-akun | staff.password.update | cek password lama, simpan hash baru |
| Admin | POST | /staff/admin/check-in | admin.checkin.store | status booking jadi check-in, masuk antrean dokter |
| Admin | GET | /staff/admin/pasien/cari-nik | admin.patients.lookup | JSON pasien by NIK atau `null` |
| Admin | POST | /staff/admin/pasien | admin.patients.store | simpan pasien + booking walk-in, balas nomor antrean |
| Admin | PUT/DELETE | /staff/admin/pasien/{id} | admin.patients.update/destroy | ubah / hapus pasien |
| Admin | POST | /staff/admin/jadwal-dokter | admin.schedule.store | simpan jadwal praktik |
| Admin | POST/PUT/DELETE | /staff/admin/staf | admin.staff.* | CRUD akun dokter dan apoteker (role: doctor / pharmacist) |
| Dokter | POST | /staff/dokter/janji-temu/{id} | doctor.appointments.decide | approve / reject + notifikasi |
| Dokter | POST | /staff/dokter/pemeriksaan | doctor.exam.store | simpan pemeriksaan, antrean jadi Selesai |
| Dokter | POST | /staff/dokter/resep | doctor.prescriptions.store | simpan resep (status Menunggu) |
| Apoteker | POST/DELETE | /staff/apoteker/obat | pharmacist.medicines.* | CRUD obat (ada `id` = ubah) |
| Apoteker | PATCH | /staff/apoteker/resep/{code} | pharmacist.prescriptions.update | ubah status resep |
| Apoteker | POST | /staff/apoteker/persediaan | pharmacist.stock.store | catat stok masuk |

## Usulan tabel

`users` (name, username, password, role: admin|doctor|pharmacist, phone, sip, poli_id, status) |
`patients` (rm, nik unique, name, gender, birth, phone, email, address, blood_type, allergies, emergency_name/relation/phone, insurance_type, insurance_number, password nullable untuk akun portal) |
`poli` | `doctor_schedules` (doctor_id, day, start, end, room) |
`bookings` (code, patient_id, doctor_id, poli_id, date, time, complaint, queue_no, type: online|walkin, status: pending|approved|rejected|checked_in|done|cancelled) |
`examinations` (booking_id, doctor_id, complaint, bp, temp, pulse, diagnosis, note) |
`prescriptions` (code, examination_id, status) + `prescription_items` (medicine_id, qty, dose, rule) |
`medicines` (code, name, category, unit, price, min_stock) + `stock_movements` (medicine_id, qty, expiry, batch, type).

## Bentuk data

Lihat method di `MockClinicRepository.php`: kunci array di sana adalah kontrak yang dipakai view.
