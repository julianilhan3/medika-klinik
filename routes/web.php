<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PharmacistController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Middleware\PatientAuth;
use App\Http\Middleware\StaffRole;
use Illuminate\Support\Facades\Route;

/*
| Daftar route = daftar pekerjaan backend. Setiap route sudah memiliki
| validasi dan komentar "BACKEND:" di controller tempat logika perlu diisi.
*/

Route::redirect('/', '/masuk');

// ===== Portal pasien =====
Route::get('/masuk', [PortalController::class, 'showLogin'])->name('portal.login');
Route::post('/masuk', [PortalController::class, 'login'])->name('portal.login.submit');
Route::get('/daftar', [PortalController::class, 'showRegister'])->name('portal.register');
Route::post('/daftar', [PortalController::class, 'register'])->name('portal.register.submit');

Route::middleware(PatientAuth::class)->group(function () {
    Route::post('/keluar', [PortalController::class, 'logout'])->name('portal.logout');
    Route::get('/beranda', [PortalController::class, 'home'])->name('portal.home');
    Route::get('/jadwal-dokter', [PortalController::class, 'doctors'])->name('portal.doctors');
    Route::get('/booking', [PortalController::class, 'booking'])->name('booking');
    Route::get('/booking/slots', [PortalController::class, 'bookingSlots'])
    ->name('booking.slots');
    Route::post('/booking', [PortalController::class, 'storeBooking'])->name('booking.store');
    Route::get('/booking/berhasil', [PortalController::class, 'bookingDone'])->name('booking.done');
    Route::get('/tiket', [PortalController::class, 'tickets'])->name('tickets.index');
    Route::get('/tiket/{code}', [PortalController::class, 'ticket'])->name('tickets.show');
    Route::delete('/tiket/{code}', [PortalController::class, 'cancelTicket'])->name('tickets.cancel');
    Route::get('/riwayat-medis', [PortalController::class, 'records'])->name('portal.records');
    Route::get('/riwayat-medis/{id}', [PortalController::class, 'record'])->name('portal.records.show');
    Route::get('/resep-saya', [PortalController::class, 'prescriptions'])->name('portal.prescriptions');
    Route::get('/resep-saya/{code}', [PortalController::class, 'prescription'])->name('portal.prescriptions.show');
    Route::get('/profil', [PortalController::class, 'profile'])->name('portal.profile');
    Route::put('/profil', [PortalController::class, 'updateProfile'])->name('portal.profile.update');
    Route::get('/keamanan-akun', [PortalController::class, 'password'])->name('portal.password');
    Route::put('/keamanan-akun', [PortalController::class, 'updatePassword'])->name('portal.password.update');
});



// ===== Staf =====
Route::prefix('staff')->group(function () {
    Route::get('/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login', [StaffAuthController::class, 'login'])->name('staff.login.submit');

    Route::middleware(StaffRole::class.':any')->group(function () {
        Route::post('/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');
        Route::get('/keamanan-akun', [StaffAuthController::class, 'password'])->name('staff.password');
        Route::put('/keamanan-akun', [StaffAuthController::class, 'updatePassword'])->name('staff.password.update');
    });

    // Admin
    Route::prefix('admin')->name('admin.')->middleware(StaffRole::class.':admin')->controller(AdminController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/antrean', 'queue')->name('queue');
        Route::get('/check-in', 'checkin')->name('checkin');
        Route::post('/check-in', 'doCheckin')->name('checkin.store');
        Route::get('/reservasi', 'bookings')->name('bookings');
        Route::patch('/reservasi/{code}', 'updateBooking')->name('bookings.update');
        Route::get('/pasien', 'patients')->name('patients.index');
        Route::get('/pasien/baru', 'createPatient')->name('patients.create');
        Route::get('/pasien/cari-nik', 'lookupNik')->name('patients.lookup');
        Route::get('/pasien/slots', 'patientSlots')->name('patients.slots');
        Route::post('/pasien', 'storePatient')->name('patients.store');
        Route::put('/pasien/{id}', 'updatePatient')->name('patients.update');
        Route::delete('/pasien/{id}', 'destroyPatient')->name('patients.destroy');
        Route::get('/jadwal-praktik', 'schedule')->name('schedule');
        Route::post('/jadwal-praktik', 'storeSchedule')->name('schedule.store');
        Route::post('/jadwal-praktik/cuti', 'blockLeave')->name('schedule.leave');
        Route::get('/laporan', 'reports')->name('reports');
        Route::get('/staf', 'staff')->name('staff.index');
        Route::post('/staf', 'storeStaff')->name('staff.store');
        Route::put('/staf/{id}', 'updateStaff')->name('staff.update');
        Route::delete('/staf/{id}', 'destroyStaff')->name('staff.destroy');
    });

    // Dokter
    Route::prefix('dokter')->name('doctor.')->middleware(StaffRole::class.':doctor')->controller(DoctorController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::post('/janji-temu/{id}', 'decide')->name('appointments.decide');
        Route::get('/antrean', 'queue')->name('queue');
        Route::post('/antrean/{no}/panggil', 'call')->name('queue.call');
        Route::post('/antrean/{no}/tidak-hadir', 'absent')->name('queue.absent');
        Route::post('/antrean/{no}/mulai', 'start')->name('queue.start');
        Route::get('/rekam-medis/{no}', 'record')->name('record');
        Route::get('/pemeriksaan/{no}', 'exam')->name('exam');
        Route::post('/pemeriksaan/{no}', 'storeExam')->name('exam.store');
        Route::get('/resep/{no}', 'rxCreate')->name('rx.create');
        Route::post('/resep/{no}', 'rxStore')->name('rx.store');
        Route::get('/riwayat', 'history')->name('history');
        Route::get('/jadwal-praktik', 'schedule')->name('schedule');
    });

    // Apoteker
    Route::prefix('apoteker')->name('pharmacist.')->middleware(StaffRole::class.':pharmacist')->controller(PharmacistController::class)->group(function () {
        Route::get('/', 'summary')->name('summary');
        Route::get('/resep', 'prescriptions')->name('prescriptions');
        Route::get('/resep/{code}', 'showPrescription')->name('prescriptions.show');
        Route::patch('/resep/{code}', 'advancePrescription')->name('prescriptions.update');
        Route::post('/resep/{code}/obat-tidak-tersedia', 'markUnavailable')->name('prescriptions.unavailable');
        Route::get('/penyerahan', 'handover')->name('handover');
        Route::get('/penyerahan/{code}', 'showHandover')->name('handover.show');
        Route::post('/penyerahan/{code}/panggil', 'callPatient')->name('handover.call');
        Route::post('/penyerahan/{code}/konfirmasi', 'confirmHandover')->name('handover.confirm');
        Route::get('/riwayat-penyerahan', 'history')->name('history');
        Route::get('/obat', 'medicines')->name('medicines');
        Route::post('/obat', 'storeMedicine')->name('medicines.store');
        Route::delete('/obat/{id}', 'destroyMedicine')->name('medicines.destroy');
        Route::get('/persediaan', 'stock')->name('stock');
        Route::post('/persediaan', 'addStock')->name('stock.store');
    });
});
