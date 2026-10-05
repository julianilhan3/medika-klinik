<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Medika Klinik (data sementara untuk front end)
|--------------------------------------------------------------------------
| - staff_users : akun demo. BACKEND: ganti dengan tabel `users` + kolom `role`.
| - menus       : menu sidebar per peran. Tambah/ubah menu cukup di sini.
|                 route = tujuan link, match = pola route untuk status aktif.
*/

return [
    'name' => 'Medika Klinik',

    'staff_users' => [
        ['username' => 'rina.admin',    'password' => 'password123', 'name' => 'Rina Anggraini',         'role' => 'admin'],
        ['username' => 'andi.dokter',   'password' => 'password123', 'name' => 'dr. Andi Pratama',       'role' => 'doctor'],
        ['username' => 'sari.apoteker', 'password' => 'password123', 'name' => 'Sari Wulandari, S.Farm', 'role' => 'pharmacist'],
    ],

    'roles' => [
        'patient'    => ['label' => 'Pasien',   'home' => 'portal.home'],
        'admin'      => ['label' => 'Admin',    'home' => 'admin.dashboard'],
        'doctor'     => ['label' => 'Dokter',   'home' => 'doctor.dashboard'],
        'pharmacist' => ['label' => 'Apoteker', 'home' => 'pharmacist.summary'],
    ],

    'menus' => [
        'patient' => [
            ['label' => 'Beranda',        'route' => 'portal.home',          'match' => 'portal.home',          'icon' => 'lucide:layout-grid'],
            ['label' => 'Booking Online', 'route' => 'booking',              'match' => 'booking*',             'icon' => 'lucide:calendar-plus'],
            ['label' => 'Tiket & Antrean','route' => 'tickets.index',        'match' => 'tickets.*',            'icon' => 'lucide:ticket'],
            ['label' => 'Riwayat Medis',  'route' => 'portal.records',       'match' => 'portal.records',       'icon' => 'lucide:file-heart'],
            ['label' => 'Resep Saya',     'route' => 'portal.prescriptions', 'match' => 'portal.prescriptions', 'icon' => 'lucide:pill'],
            ['label' => 'Profil Saya',    'route' => 'portal.profile',       'match' => 'portal.profile*',      'icon' => 'lucide:user-round'],
            ['label' => 'Keamanan akun',  'route' => 'portal.password',      'match' => 'portal.password*',     'icon' => 'lucide:shield-check'],
        ],
        'admin' => [
            ['label' => 'Ringkasan',       'route' => 'admin.dashboard',      'match' => 'admin.dashboard',   'icon' => 'lucide:layout-grid'],
            ['label' => 'Antrean',         'route' => 'admin.queue',          'match' => 'admin.queue',       'icon' => 'lucide:list-ordered'],
            ['label' => 'Check-in Pasien', 'route' => 'admin.checkin',        'match' => 'admin.checkin*',    'icon' => 'lucide:scan-line'],
            ['label' => 'Reservasi',       'route' => 'admin.bookings',       'match' => 'admin.bookings*',   'icon' => 'lucide:clipboard-list'],
            ['label' => 'Data Pasien',     'route' => 'admin.patients.index', 'match' => 'admin.patients*',   'icon' => 'lucide:users'],
            ['label' => 'Kelola Staf',     'route' => 'admin.staff.index',    'match' => 'admin.staff*',      'icon' => 'lucide:user-cog'],
            ['label' => 'Jadwal Praktik',  'route' => 'admin.schedule',       'match' => 'admin.schedule*',   'icon' => 'lucide:calendar-days'],
            ['label' => 'Laporan',         'route' => 'admin.reports',        'match' => 'admin.reports',     'icon' => 'lucide:bar-chart-3'],
            ['label' => 'Keamanan akun',   'route' => 'staff.password',       'match' => 'staff.password*',   'icon' => 'lucide:shield-check'],
        ],
        // Menu utama dokter
        'doctor' => [
            ['label' => 'Ringkasan',           'route' => 'doctor.dashboard', 'match' => 'doctor.dashboard',                  'icon' => 'lucide:layout-grid'],
            ['label' => 'Antrean Pasien',      'route' => 'doctor.queue',     'match' => ['doctor.queue*', 'doctor.record'],  'icon' => 'lucide:users'],
            ['label' => 'Riwayat Pemeriksaan', 'route' => 'doctor.history',   'match' => 'doctor.history',                    'icon' => 'lucide:history'],
            ['label' => 'Jadwal Praktik',      'route' => 'doctor.schedule',  'match' => 'doctor.schedule',                   'icon' => 'lucide:calendar-days'],
            ['label' => 'Keamanan akun',       'route' => 'staff.password',   'match' => 'staff.password*',                   'icon' => 'lucide:shield-check'],
        ],
        // Menu saat dokter sedang memeriksa pasien (halaman Pemeriksaan dan Resep). params = butuh nomor antrean
        'doctor_exam' => [
            ['label' => 'Dasbor',         'route' => 'doctor.dashboard', 'match' => 'doctor.dashboard', 'icon' => 'lucide:layout-grid'],
            ['label' => 'Antrean Pasien', 'route' => 'doctor.queue',     'match' => 'doctor.queue*',    'icon' => 'lucide:users'],
            ['label' => 'Pemeriksaan',    'route' => 'doctor.exam',      'match' => 'doctor.exam*',     'icon' => 'lucide:stethoscope',  'params' => true],
            ['label' => 'Rekam Medis',    'route' => 'doctor.record',    'match' => 'doctor.record',    'icon' => 'lucide:folder-heart', 'params' => true],
            ['label' => 'Resep Obat',     'route' => 'doctor.rx.create', 'match' => 'doctor.rx*',       'icon' => 'lucide:pill',         'params' => true],
        ],
        'pharmacist' => [
            ['label' => 'Ringkasan',            'route' => 'pharmacist.summary',       'match' => 'pharmacist.summary',        'icon' => 'lucide:layout-grid'],
            ['label' => 'Resep Obat',           'route' => 'pharmacist.prescriptions', 'match' => 'pharmacist.prescriptions*', 'icon' => 'lucide:file-text'],
            ['label' => 'Penyerahan Obat',      'route' => 'pharmacist.handover',      'match' => 'pharmacist.handover*',      'icon' => 'lucide:hand-helping'],
            ['label' => 'Riwayat Penyerahan',   'route' => 'pharmacist.history',       'match' => 'pharmacist.history',        'icon' => 'lucide:history'],
            ['label' => 'Data Obat',            'route' => 'pharmacist.medicines',     'match' => 'pharmacist.medicines*',     'icon' => 'lucide:pill'],
            ['label' => 'Persediaan',           'route' => 'pharmacist.stock',         'match' => 'pharmacist.stock*',         'icon' => 'lucide:package'],
            ['label' => 'Keamanan akun',        'route' => 'staff.password',           'match' => 'staff.password*',           'icon' => 'lucide:shield-check'],
        ],
    ],
];
