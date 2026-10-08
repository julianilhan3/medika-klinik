<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DoctorSchedule;
use App\Models\Examination;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ClinicSeeder::class);
    }

    public function test_data_dasar_klinik_tersedia(): void
    {
        $this->assertDatabaseHas('poli', [
            'name' => 'Poli Umum',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'drbudi',
            'role' => 'doctor',
        ]);

        $this->assertDatabaseHas('medicines', [
            'code' => 'MED-001',
        ]);

        $this->assertDatabaseHas('patients', [
            'rm' => 'RM-0001',
        ]);
    }

    public function test_jadwal_dokter_tersimpan(): void
    {
        $doctor = User::where('username', 'drbudi')->firstOrFail();

        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day' => 6,
            'start' => '08:00',
            'end' => '12:00',
            'room' => 'Ruang 1',
            'quota' => 20,
        ]);

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $doctor->id,
            'day' => 6,
            'room' => 'Ruang 1',
        ]);
    }

    public function test_booking_bisa_dibuat_dengan_nomor_antrean_string(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();

        $booking = Booking::create([
            'code' => 'TEST-001',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '08:00',
            'type' => 'walkin',
            'status' => 'checked_in',
            'queue_no' => 'U-001',
            'complaint' => 'Demam',
        ]);

        $this->assertDatabaseHas('bookings', [
            'code' => 'TEST-001',
            'queue_no' => 'U-001',
            'status' => 'checked_in',
        ]);

        $this->assertIsString($booking->queue_no);
    }

    public function test_status_serving_bisa_digunakan(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();

        $booking = Booking::create([
            'code' => 'TEST-002',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '09:00',
            'type' => 'walkin',
            'status' => 'checked_in',
            'queue_no' => 'U-002',
            'complaint' => 'Batuk',
        ]);

        $booking->update([
            'status' => 'serving',
            'started_at' => now(),
        ]);

        $this->assertDatabaseHas('bookings', [
            'code' => 'TEST-002',
            'status' => 'serving',
        ]);
    }

    public function test_pemeriksaan_bisa_disimpan(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();

        $booking = Booking::create([
            'code' => 'TEST-003',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '10:00',
            'type' => 'walkin',
            'status' => 'serving',
            'queue_no' => 'U-003',
            'complaint' => 'Demam',
        ]);

        $exam = Examination::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'Demam',
            'bp' => '120/80',
            'temp' => 37.5,
            'pulse' => 80,
            'weight' => 65,
            'diagnosis' => 'Influenza',
            'note' => 'Pemeriksaan fisik normal',
            'anamnesis' => 'Demam sejak kemarin',
            'icd' => 'J11',
            'therapy' => null,
            'education' => 'Istirahat cukup',
        ]);

        $this->assertDatabaseHas('examinations', [
            'id' => $exam->id,
            'booking_id' => $booking->id,
            'diagnosis' => 'Influenza',
            'education' => 'Istirahat cukup',
        ]);
    }

    public function test_resep_dan_item_resep_bisa_dibuat(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();
        $medicine = Medicine::where('code', 'MED-001')->firstOrFail();

        $booking = Booking::create([
            'code' => 'TEST-004',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '11:00',
            'type' => 'walkin',
            'status' => 'serving',
            'queue_no' => 'U-004',
        ]);

        $exam = Examination::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'Demam',
            'bp' => '120/80',
            'temp' => 37.5,
            'pulse' => 80,
            'weight' => 65,
            'diagnosis' => 'Influenza',
            'education' => 'Istirahat cukup',
        ]);

        $prescription = Prescription::create([
            'code' => 'RX-TEST-001',
            'examination_id' => $exam->id,
            'status' => 'Menunggu',
            'pharmacy_no' => null,
            'note' => 'Minum setelah makan',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'qty' => 2,
            'dose' => '500 mg',
            'rule' => '3 x sehari',
            'form' => 'Tablet',
        ]);

        $this->assertDatabaseHas('prescriptions', [
            'code' => 'RX-TEST-001',
            'status' => 'Menunggu',
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'qty' => 2,
        ]);
    }

    public function test_status_resep_bisa_berubah(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();

        $booking = Booking::create([
            'code' => 'TEST-005',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '11:30',
            'type' => 'walkin',
            'status' => 'done',
            'queue_no' => 'U-005',
        ]);

        $exam = Examination::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'Batuk',
            'bp' => '120/80',
            'temp' => 36.8,
            'pulse' => 78,
            'weight' => 65,
            'diagnosis' => 'Batuk',
        ]);

        $prescription = Prescription::create([
            'code' => 'RX-TEST-002',
            'examination_id' => $exam->id,
            'status' => 'Menunggu',
        ]);

        $prescription->update([
            'status' => 'Diproses',
        ]);

        $prescription->update([
            'status' => 'Siap',
        ]);

        $this->assertDatabaseHas('prescriptions', [
            'code' => 'RX-TEST-002',
            'status' => 'Siap',
        ]);
    }

    public function test_stok_masuk_menambah_stok_dan_mencatat_mutasi(): void
    {
        $medicine = Medicine::where('code', 'MED-001')->firstOrFail();

        $oldStock = $medicine->stock;

        $medicine->increment('stock', 10);

        StockMovement::create([
            'medicine_id' => $medicine->id,
            'qty' => 10,
            'expiry' => $medicine->expiry,
            'batch' => 'BATCH-TEST',
            'type' => 'in',
            'note' => 'Test stok masuk',
        ]);

        $medicine->refresh();

        $this->assertEquals($oldStock + 10, $medicine->stock);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'qty' => 10,
            'type' => 'in',
            'batch' => 'BATCH-TEST',
        ]);
    }

    public function test_penyerahan_obat_mengurangi_stok(): void
    {
        $patient = Patient::where('rm', 'RM-0001')->firstOrFail();
        $doctor = User::where('username', 'drbudi')->firstOrFail();
        $poli = Poli::where('name', 'Poli Umum')->firstOrFail();
        $medicine = Medicine::where('code', 'MED-001')->firstOrFail();

        $medicine->update([
            'stock' => 10,
        ]);

        $booking = Booking::create([
            'code' => 'TEST-006',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => now()->toDateString(),
            'time' => '12:00',
            'type' => 'walkin',
            'status' => 'done',
            'queue_no' => 'U-006',
        ]);

        $exam = Examination::create([
            'booking_id' => $booking->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'Demam',
            'bp' => '120/80',
            'temp' => 37,
            'pulse' => 80,
            'weight' => 65,
            'diagnosis' => 'Demam',
        ]);

        $prescription = Prescription::create([
            'code' => 'RX-TEST-003',
            'examination_id' => $exam->id,
            'status' => 'Siap',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'qty' => 2,
            'dose' => '500 mg',
            'rule' => '3 x sehari',
            'form' => 'Tablet',
        ]);

        $medicine->decrement('stock', 2);

        StockMovement::create([
            'medicine_id' => $medicine->id,
            'qty' => 2,
            'expiry' => $medicine->expiry,
            'batch' => null,
            'type' => 'out',
            'note' => 'Penyerahan resep RX-TEST-003',
        ]);

        $medicine->refresh();

        $this->assertEquals(8, $medicine->stock);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'qty' => 2,
            'type' => 'out',
        ]);
    }
    public function test_admin_bisa_mengakses_halaman_admin(): void
{
    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/admin')
        ->assertOk();
}

public function test_doctor_bisa_mengakses_halaman_dokter(): void
{
    $user = User::where('username', 'drbudi')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/dokter')
        ->assertOk();
}

public function test_pharmacist_bisa_mengakses_halaman_apoteker(): void
{
    $user = User::where('role', 'pharmacist')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/apoteker')
        ->assertOk();
}

public function test_admin_ditolak_masuk_halaman_dokter(): void
{
    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/dokter')
        ->assertRedirectToRoute('admin.dashboard');
}

public function test_admin_ditolak_masuk_halaman_apoteker(): void
{
    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/apoteker')
        ->assertRedirectToRoute('admin.dashboard');
}

public function test_doctor_ditolak_masuk_halaman_admin(): void
{
    $user = User::where('username', 'drbudi')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/admin')
        ->assertRedirectToRoute('doctor.dashboard');
}

public function test_doctor_ditolak_masuk_halaman_apoteker(): void
{
    $user = User::where('username', 'drbudi')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/apoteker')
        ->assertRedirectToRoute('doctor.dashboard');
}

public function test_pharmacist_ditolak_masuk_halaman_admin(): void
{
    $user = User::where('role', 'pharmacist')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/admin')
        ->assertRedirectToRoute('pharmacist.summary');
}

public function test_pharmacist_ditolak_masuk_halaman_dokter(): void
{
    $user = User::where('role', 'pharmacist')->firstOrFail();

    $this->actingAs($user)
        ->get('/staff/dokter')
        ->assertRedirectToRoute('pharmacist.summary');
}
}