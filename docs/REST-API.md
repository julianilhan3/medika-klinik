# REST API Medika Klinik

Dokumentasi REST API untuk modul jadwal dokter dan pendaftaran pasien pada project Medika Klinik.

## Base URL

```text
http://localhost/medika-klinik/public/api
```

## Daftar Endpoint

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/jadwal-dokter` | Mendapatkan daftar dokter |
| GET | `/jadwal-dokter/{doctorId}` | Mendapatkan detail jadwal dokter |
| GET | `/jadwal-dokter/{doctorId}/slots?date=YYYY-MM-DD` | Mendapatkan slot dokter berdasarkan tanggal |
| POST | `/pendaftaran` | Membuat pendaftaran pasien |
| GET | `/pendaftaran/{code}` | Mendapatkan detail pendaftaran |

---

# 1. Jadwal Dokter

## 1.1 Daftar Dokter

### Request

```http
GET /api/jadwal-dokter
```

### URL Lokal

```text
http://localhost/medika-klinik/public/api/jadwal-dokter
```

### Contoh Response

```json
{
    "success": true,
    "message": "Data jadwal dokter berhasil diambil",
    "data": [
        {
            "id": "D-001",
            "name": "Dr. Andi Wijaya",
            "poli": "Poli Anak",
            "sip": "SIP-ANDI-003",
            "phone": "081234567893",
            "username": "drandi",
            "status": "Aktif"
        },
        {
            "id": "D-002",
            "name": "Dr. Budi Santoso",
            "poli": "Poli Umum",
            "sip": "SIP-BUDI-001",
            "phone": "081234567891",
            "username": "drbudi",
            "status": "Aktif"
        },
        {
            "id": "D-003",
            "name": "Dr. Siti Rahma",
            "poli": "Poli Gigi",
            "sip": "SIP-SITI-002",
            "phone": "081234567892",
            "username": "drsiti",
            "status": "Aktif"
        }
    ]
}
```

---

## 1.2 Detail Jadwal Dokter

### Request

```http
GET /api/jadwal-dokter/{doctorId}
```

### Contoh

```text
http://localhost/medika-klinik/public/api/jadwal-dokter/D-002
```

### Contoh Response

```json
{
    "success": true,
    "message": "Detail jadwal dokter berhasil diambil",
    "data": {
        "doctor": {
            "id": "D-002",
            "name": "Dr. Budi Santoso",
            "poli": "Poli Umum"
        },
        "schedule": [
            {
                "day": 0,
                "start": "08:00",
                "end": "12:00",
                "poli": "Poli Umum",
                "quota": 10,
                "booked": 0
            }
        ]
    }
}
```

> `day` pada implementasi project menggunakan indeks `0` sampai `6` untuk Senin sampai Minggu.

---

## 1.3 Slot Jadwal Dokter

### Request

```http
GET /api/jadwal-dokter/{doctorId}/slots?date=YYYY-MM-DD
```

### Contoh

```text
http://localhost/medika-klinik/public/api/jadwal-dokter/D-002/slots?date=2026-10-05
```

### Contoh Response

```json
{
    "success": true,
    "message": "Slot jadwal dokter berhasil diambil",
    "data": {
        "doctor_id": "D-002",
        "date": "2026-10-05",
        "slots": [
            {
                "time": "08:00",
                "available": true,
                "left": 10
            },
            {
                "time": "09:00",
                "available": true,
                "left": 10
            },
            {
                "time": "10:00",
                "available": true,
                "left": 10
            },
            {
                "time": "11:00",
                "available": true,
                "left": 10
            }
        ]
    }
}
```

Keterangan:

- `time`: jam slot.
- `available`: apakah slot masih dapat dipilih.
- `left`: sisa kuota pada slot.

---

# 2. Pendaftaran Pasien

## 2.1 Membuat Pendaftaran

### Request

```http
POST /api/pendaftaran
```

### URL Lokal

```text
http://localhost/medika-klinik/public/api/pendaftaran
```

### Headers

```http
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
    "patient_id": 1,
    "doctor_id": "D-002",
    "poli_id": 1,
    "date": "2026-10-05",
    "time": "08:00",
    "complaint": "Demam dan sakit kepala"
}
```

### Parameter

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `patient_id` | Integer | Ya | ID pasien |
| `doctor_id` | String | Ya | Kode dokter, contoh `D-002` |
| `poli_id` | Integer | Ya | ID poli |
| `date` | Date | Ya | Format `YYYY-MM-DD` |
| `time` | Time | Ya | Format `HH:mm` |
| `complaint` | String | Tidak | Keluhan pasien |

### Response Berhasil

HTTP Status:

```text
201 Created
```

Contoh response yang sudah diuji:

```json
{
    "success": true,
    "message": "Pendaftaran pasien berhasil.",
    "data": {
        "id": "1",
        "patient_id": "1",
        "patient": "Budi Pasien",
        "rm": "RM-0001",
        "nik": "3173000000000001",
        "birth": "1998-01-10",
        "phone": "081200000001",
        "gender": "L",
        "poli": "Poli Umum",
        "doctor": "Dr. Budi Santoso",
        "queue": "U-001",
        "code": "BK-20261005-001",
        "date": "5 October 2026",
        "time": "08:00",
        "type": "online",
        "status": "pending",
        "state": "Menunggu Persetujuan",
        "complaint": "Demam dan sakit kepala"
    }
}
```

---

## 2.2 Detail Pendaftaran

### Request

```http
GET /api/pendaftaran/{code}
```

### Contoh

```text
http://localhost/medika-klinik/public/api/pendaftaran/BK-20261005-001
```

### Contoh Response

```json
{
    "success": true,
    "message": "Detail pendaftaran berhasil diambil.",
    "data": {
        "id": "1",
        "patient_id": "1",
        "patient": "Budi Pasien",
        "rm": "RM-0001",
        "nik": "3173000000000001",
        "birth": "1998-01-10",
        "phone": "081200000001",
        "gender": "L",
        "poli": "Poli Umum",
        "doctor": "Dr. Budi Santoso",
        "queue": "U-001",
        "code": "BK-20261005-001",
        "date": "5 October 2026",
        "time": "08:00",
        "type": "online",
        "status": "pending",
        "state": "Menunggu Persetujuan",
        "complaint": "Demam dan sakit kepala"
    }
}
```

---

# 3. Validasi dan Error Response

## 3.1 Dokter Tidak Ditemukan

Contoh request menggunakan `D-999`.

HTTP Status:

```text
404 Not Found
```

Response:

```json
{
    "success": false,
    "message": "Dokter tidak ditemukan.",
    "data": null
}
```

## 3.2 Poli Tidak Sesuai Dokter

HTTP Status:

```text
422 Unprocessable Content
```

Response:

```json
{
    "success": false,
    "message": "Poli tidak sesuai dengan dokter.",
    "data": null
}
```

## 3.3 Jam di Luar Jadwal

HTTP Status:

```text
422 Unprocessable Content
```

Response:

```json
{
    "success": false,
    "message": "Dokter tidak memiliki jadwal pada jam tersebut.",
    "data": null
}
```

## 3.4 Kuota Penuh

HTTP Status:

```text
422 Unprocessable Content
```

Response:

```json
{
    "success": false,
    "message": "Kuota pada jam tersebut sudah penuh.",
    "data": {
        "time": "08:00",
        "available": false,
        "left": 0
    }
}
```

---

# 4. Pengujian API

| No. | Skenario | Expected | Hasil |
|---:|---|---|---|
| 1 | Mendapatkan daftar dokter | `200` | ✅ Berhasil |
| 2 | Mendapatkan detail jadwal dokter | `200` | ✅ Berhasil |
| 3 | Mendapatkan slot dokter | `200` | ✅ Berhasil |
| 4 | Pendaftaran pasien valid | `201` | ✅ Berhasil |
| 5 | Melihat detail pendaftaran | `200` | ✅ Berhasil |
| 6 | Jam di luar jadwal | `422` | ✅ Ditolak |
| 7 | Poli tidak sesuai dokter | `422` | ✅ Ditolak |
| 8 | Dokter tidak ditemukan | `404` | ✅ Ditolak |
| 9 | Kuota penuh | `422` | ✅ Ditolak |

---

# 5. Contoh Pengujian dengan Postman

## Pendaftaran berhasil

```text
POST http://localhost/medika-klinik/public/api/pendaftaran
```

Body:

```json
{
    "patient_id": 1,
    "doctor_id": "D-002",
    "poli_id": 1,
    "date": "2026-10-05",
    "time": "08:00",
    "complaint": "Demam dan sakit kepala"
}
```

## Jam tidak tersedia

```text
POST http://localhost/medika-klinik/public/api/pendaftaran
```

Body:

```json
{
    "patient_id": 1,
    "doctor_id": "D-002",
    "poli_id": 1,
    "date": "2026-10-05",
    "time": "15:00",
    "complaint": "Demam"
}
```

Expected:

```text
422 Unprocessable Content
```

---

# 6. Struktur Alur API

```text
Client / Postman / Flutter
          |
          v
      routes/api.php
          |
          v
      API Controller
          |
          v
    ClinicRepository
          |
          v
      Eloquent Model
          |
          v
       MySQL
```

## Alur Pendaftaran

```text
POST /api/pendaftaran
        |
        v
Validasi request
        |
        v
Validasi dokter
        |
        v
Validasi poli
        |
        v
Cek jadwal & slot
        |
        v
Cek kuota
        |
        v
createBooking()
        |
        v
Kode Booking + Nomor Antrean
```

---

# 7. Catatan Implementasi

- API menggunakan route API Laravel melalui `routes/api.php`.
- Controller API menggunakan `ClinicRepository` sehingga logika data tetap mengikuti arsitektur repository project.
- Pendaftaran online menggunakan tipe `online` dan status awal `pending`.
- Kode booking dibuat oleh repository.
- Nomor antrean dibuat oleh repository berdasarkan poli dan tanggal.
- Slot dianggap tidak tersedia ketika quota pada jam tersebut telah terpenuhi.
