<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $umum = Poli::updateOrCreate(
            ['name' => 'Poli Umum'],
            []
        );

        $gigi = Poli::updateOrCreate(
            ['name' => 'Poli Gigi'],
            []
        );

        $anak = Poli::updateOrCreate(
            ['name' => 'Poli Anak'],
            []
        );

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@medika.test',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );

        User::updateOrCreate(
            ['username' => 'drbudi'],
            [
                'name' => 'Dr. Budi Santoso',
                'email' => 'drbudi@medika.test',
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'phone' => '081234567891',
                'sip' => 'SIP-BUDI-001',
                'poli_id' => $umum->id,
            ]
        );

        User::updateOrCreate(
            ['username' => 'drsiti'],
            [
                'name' => 'Dr. Siti Rahma',
                'email' => 'drsiti@medika.test',
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'phone' => '081234567892',
                'sip' => 'SIP-SITI-002',
                'poli_id' => $gigi->id,
            ]
        );

        User::updateOrCreate(
            ['username' => 'drandi'],
            [
                'name' => 'Dr. Andi Wijaya',
                'email' => 'drandi@medika.test',
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'phone' => '081234567893',
                'sip' => 'SIP-ANDI-003',
                'poli_id' => $anak->id,
            ]
        );

        User::updateOrCreate(
            ['username' => 'apoteker'],
            [
                'name' => 'Apoteker Medika',
                'email' => 'apoteker@medika.test',
                'password' => Hash::make('password'),
                'role' => 'pharmacist',
                'phone' => '081234567894',
            ]
        );

        Patient::updateOrCreate(
            ['nik' => '3173000000000001'],
            [
                'rm' => 'RM-0001',
                'name' => 'Budi Pasien',
                'gender' => 'L',
                'birth' => '1998-01-10',
                'phone' => '081200000001',
                'email' => 'pasien1@medika.test',
                'address' => 'Jakarta',
                'blood_type' => 'O',
                'allergies' => null,
                'emergency_name' => 'Siti',
                'emergency_relation' => 'Ibu',
                'emergency_phone' => '081200000002',
                'insurance_type' => 'BPJS',
                'insurance_number' => '0001234567890',
                'password' => Hash::make('password'),
            ]
        );

        Patient::updateOrCreate(
            ['nik' => '3173000000000002'],
            [
                'rm' => 'RM-0002',
                'name' => 'Ani Pasien',
                'gender' => 'P',
                'birth' => '2000-05-20',
                'phone' => '081200000003',
                'email' => 'pasien2@medika.test',
                'address' => 'Jakarta',
                'blood_type' => 'A',
                'allergies' => null,
                'emergency_name' => 'Andi',
                'emergency_relation' => 'Ayah',
                'emergency_phone' => '081200000004',
                'insurance_type' => 'Umum',
                'insurance_number' => null,
                'password' => Hash::make('password'),
            ]
        );

        Medicine::updateOrCreate(
            ['code' => 'MED-001'],
            [
                'name' => 'Paracetamol',
                'category' => 'Analgesik',
                'unit' => 'Tablet',
                'price' => 1000,
                'stock' => 100,
                'min_stock' => 20,
                'expiry' => '2027-12-31',
                'class' => 'Bebas',
                'form' => 'Tablet',
            ]
        );

        Medicine::updateOrCreate(
            ['code' => 'MED-002'],
            [
                'name' => 'Amoxicillin',
                'category' => 'Antibiotik',
                'unit' => 'Kapsul',
                'price' => 2000,
                'stock' => 50,
                'min_stock' => 10,
                'expiry' => '2027-10-31',
                'class' => 'Keras',
                'form' => 'Kapsul',
            ]
        );

        Medicine::updateOrCreate(
            ['code' => 'MED-003'],
            [
                'name' => 'Vitamin C',
                'category' => 'Vitamin',
                'unit' => 'Tablet',
                'price' => 1500,
                'stock' => 80,
                'min_stock' => 15,
                'expiry' => '2028-01-31',
                'class' => 'Bebas',
                'form' => 'Tablet',
            ]
        );

        $this->command->info('Data awal Medika Klinik berhasil dibuat.');
    }
}