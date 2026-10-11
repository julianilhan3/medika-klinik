<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ClinicRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(
        private ClinicRepository $repo
    ) {}

    /**
     * Membuat pendaftaran pasien
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'string'],
            'poli_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'complaint' => ['nullable', 'string', 'max:1000'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Cari dokter
        |--------------------------------------------------------------------------
        */

        $doctor = collect(
            $this->repo->doctors()
        )->firstWhere('id', $validated['doctor_id']);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Dokter tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Pastikan poli sesuai dengan dokter
        |--------------------------------------------------------------------------
        */

        $poli = collect(
            $this->repo->poli()
        )->firstWhere(
            'id',
            (string) $validated['poli_id']
        );

        if (!$poli) {
            return response()->json([
                'success' => false,
                'message' => 'Poli tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if (
            mb_strtolower($doctor['poli']) !==
            mb_strtolower($poli['name'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Poli tidak sesuai dengan dokter.',
                'data' => null,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Ambil slot dokter pada tanggal yang dipilih
        |--------------------------------------------------------------------------
        */

        $slots = $this->repo->slots(
            $validated['doctor_id'],
            $validated['date']
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Cari jam yang dipilih
        |--------------------------------------------------------------------------
        */

        $selectedSlot = collect($slots)->firstWhere(
            'time',
            $validated['time']
        );

        if (!$selectedSlot) {
            return response()->json([
                'success' => false,
                'message' => 'Dokter tidak memiliki jadwal pada jam tersebut.',
                'data' => null,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Cek ketersediaan kuota
        |--------------------------------------------------------------------------
        */

        if (!$selectedSlot['available']) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota pada jam tersebut sudah penuh.',
                'data' => [
                    'time' => $selectedSlot['time'],
                    'available' => false,
                    'left' => $selectedSlot['left'],
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Buat booking
        |--------------------------------------------------------------------------
        */

        try {
            $booking = $this->repo->createBooking([
                'patient_id' => $validated['patient_id'],
                'doctor_id' => $validated['doctor_id'],
                'poli_id' => $validated['poli_id'],
                'date' => $validated['date'],
                'time' => $validated['time'],
                'type' => 'online',
                'status' => 'pending',
                'complaint' => $validated['complaint'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran pasien berhasil.',
                'data' => $booking,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    /**
     * Menampilkan detail pendaftaran berdasarkan kode booking
     */
    public function show(string $code)
    {
        $booking = $this->repo->findBooking($code);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pendaftaran berhasil diambil.',
            'data' => $booking,
        ]);
    }
}