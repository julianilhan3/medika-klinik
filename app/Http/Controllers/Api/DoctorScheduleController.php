<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ClinicRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
    public function __construct(
        private ClinicRepository $repo
    ) {}

    /**
     * GET /api/jadwal-dokter
     */
    public function index(Request $request)
    {
        $poli = $request->query('poli');

        $doctors = $this->repo->doctors($poli);

        return response()->json([
            'success' => true,
            'message' => 'Data jadwal dokter berhasil diambil',
            'data' => $doctors,
        ]);
    }

    public function show(string $doctorId)
{
    $doctor = collect(
        $this->repo->doctors()
    )->firstWhere('id', $doctorId);

    if (!$doctor) {
        return response()->json([
            'success' => false,
            'message' => 'Dokter tidak ditemukan',
            'data' => null,
        ], 404);
    }

    $schedule = $this->repo->weekSchedule($doctorId);

    return response()->json([
        'success' => true,
        'message' => 'Detail jadwal dokter berhasil diambil',
        'data' => [
            'doctor' => $doctor,
            'schedule' => $schedule,
        ],
    ]);
}

    /**
     * GET /api/jadwal-dokter/{doctorId}/slots?date=YYYY-MM-DD
     */
    public function slots(Request $request, string $doctorId)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $slots = $this->repo->slots(
            $doctorId,
            $validated['date']
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot jadwal dokter berhasil diambil',
            'data' => [
                'doctor_id' => $doctorId,
                'date' => $validated['date'],
                'slots' => $slots,
            ],
        ]);
    }
}