<?php

namespace App\Contracts;

/**
 * KONTRAK DATA FRONT END  <->  BACKEND
 *
 * Seluruh halaman membaca data lewat interface ini. Saat backend siap:
 *   1. Buat class baru (mis. EloquentClinicRepository) yang mengimplementasikan interface ini.
 *   2. Ganti binding di App\Providers\ClinicServiceProvider.
 * View tidak perlu diubah selama kunci array yang dikembalikan sama (lihat MockClinicRepository).
 */
interface ClinicRepository
{
    // Master
    public function poli(): array;
    public function doctors(?string $poli = null): array;
    public function slots(string $doctorId, string $date): array;

    // Jadwal praktik (admin & dokter)
    public function weekSchedule(string $doctorId): array;
    public function leaveConflicts(string $doctorId, string $date): array;

    // Pasien & portal pasien
    public function patients(?string $search = null): array;
    public function patientByNik(string $nik): ?array;
    public function patientProfile(string $patientId): array;
    public function patientVisits(string $patientId, ?string $search = null): array;
    public function patientPrescriptions(string $patientId): array;
    public function queueStatus(string $ticketCode): array;
    public function tickets(string $patientId): array;
    public function ticket(string $code): ?array;
    public function hasActiveTicket(string $patientId, string $poli): bool;

    // Admin: antrean, check-in, reservasi
    public function queueStats(): array;
    public function queue(array $filters = []): array;
    public function findBooking(string $code): ?array;
    public function bookings(array $filters = []): array;
    public function createBooking(array $data): array;

    // Dokter
    public function appointmentRequests(): array;
    public function doctorQueue(): array;
    public function recordVisits(string $rm): array;
    public function examHistory(?string $search = null): array;
    public function pharmacyAlerts(): array;

    // Resep & apotek
    public function prescriptions(?string $status = null): array;
    public function prescription(string $code): ?array;
    public function medicines(?string $search = null): array;

    // Admin lain
    public function staff(string $role): array;
    public function report(): array;
    public function dashboard(): array;
}
