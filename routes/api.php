<?php

use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/jadwal-dokter', [
    DoctorScheduleController::class,
    'index'
]);

Route::get('/jadwal-dokter/{doctorId}', [
    DoctorScheduleController::class,
    'show'
]);

Route::get('/jadwal-dokter/{doctorId}/slots', [
    DoctorScheduleController::class,
    'slots'
]);

Route::post('/pendaftaran', [
    RegistrationController::class,
    'store'
]);

Route::get('/pendaftaran/{code}', [
    RegistrationController::class,
    'show'
]);