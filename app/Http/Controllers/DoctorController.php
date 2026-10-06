<?php

namespace App\Http\Controllers;

use App\Contracts\ClinicRepository;
use App\Models\Booking;
use App\Models\Examination;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function __construct(private ClinicRepository $repo) {}

    private function patientOrFail(string $no): array
    {
        $p = collect($this->repo->doctorQueue())->firstWhere('no', $no);
        abort_unless($p, 404);

        return $p;
    }

    private function bookingOrFail(string $no): Booking
    {
        $p = $this->patientOrFail($no);

        $booking = null;

        if (! empty($p['code'])) {
            $booking = Booking::query()
                ->where('code', $p['code'])
                ->first();
        }

        if (! $booking && isset($p['id']) && is_numeric($p['id'])) {
            $booking = Booking::query()->find($p['id']);
        }

        if (! $booking) {
            $booking = Booking::query()
                ->whereDate('date', now()->toDateString())
                ->where('queue_no', $p['no'])
                ->first();
        }

        abort_unless($booking, 404);

        return $booking;
    }

    private function queueData(): array
    {
        $queue = collect($this->repo->doctorQueue());

        return [
            'rows' => $queue->all(),
            'stats' => [
                'total' => $queue->count(),
                'waiting' => $queue->where('status', 'Menunggu')->count(),
                'called' => $queue->where('status', 'Dipanggil')->count(),
                'done' => $queue->where('status', 'Selesai')->count(),
                'absent' => $queue->where('status', 'Tidak Hadir')->count(),
            ],
            'next' => $queue->first(
                fn ($p) => in_array(
                    $p['status'],
                    ['Dipanggil', 'Menunggu'],
                    true
                )
            ),
        ];
    }

    public function dashboard()
    {
        return view(
            'staff.doctor.dashboard',
            $this->queueData() + [
                'requests' => array_values(
                    array_filter(
                        $this->repo->appointmentRequests(),
                        fn ($a) => $a['status'] === 'Menunggu'
                    )
                ),
                'alerts' => $this->repo->pharmacyAlerts(),
            ]
        );
    }

    public function decide(Request $r, int $id)
    {
        $r->validate([
            'decision' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:255',
        ]);

        $booking = Booking::query()->findOrFail($id);

        $booking->update([
            'status' => $r->decision === 'approve'
                ? 'approved'
                : 'rejected',
            'rejection_reason' => $r->decision === 'reject'
                ? $r->reason
                : null,
        ]);

        return back()->with(
            'status',
            $r->decision === 'approve'
                ? 'Janji temu disetujui.'
                : 'Janji temu ditolak.'
        );
    }

    public function queue()
    {
        return view('staff.doctor.queue', $this->queueData());
    }

    public function call(string $no)
{
    $p = $this->patientOrFail($no);
    $booking = $this->bookingOrFail($no);

    if ($booking->status === 'done') {
        return back()->with(
            'error',
            'Pasien sudah selesai diperiksa.'
        );
    }

    if ($booking->status === 'cancelled') {
        return back()->with(
            'error',
            'Pasien sudah ditandai tidak hadir.'
        );
    }

    Booking::query()
        ->where('doctor_id', $booking->doctor_id)
        ->whereDate('date', $booking->date)
        ->where('status', 'serving')
        ->update([
            'status' => 'checked_in',
        ]);

    $booking->update([
        'status' => 'serving',
    ]);

    return back()
        ->with(
            'status',
            "Pasien {$p['patient']} ({$no}) dipanggil."
        )
        ->with('called', $no);
}

    public function absent(string $no)
    {
        $p = $this->patientOrFail($no);
        $booking = $this->bookingOrFail($no);

        if ($booking->status === 'done') {
            return back()->with(
                'error',
                'Pasien sudah selesai diperiksa.'
            );
        }

        $booking->update([
            'status' => 'cancelled',
            'rejection_reason' => 'Tidak Hadir',
        ]);

        return back()->with(
            'status',
            "{$p['patient']} ditandai tidak hadir."
        );
    }

   public function start(string $no)
{
    $this->patientOrFail($no);
    $booking = $this->bookingOrFail($no);

    if ($booking->status !== 'serving') {
        return back()->with(
            'error',
            'Pasien harus dipanggil terlebih dahulu.'
        );
    }

    $booking->update([
        'started_at' => now(),
    ]);

    return redirect()
        ->route('doctor.record', $no)
        ->with(
            'status',
            'Pemeriksaan dimulai. Tinjau rekam medis sebelum melanjutkan.'
        );
}

    public function record(string $no)
    {
        $p = $this->patientOrFail($no);

        return view('staff.doctor.record', [
            'p' => $p,
            'visits' => $this->repo->recordVisits($p['rm']),
        ]);
    }

    public function exam(string $no)
    {
        return view(
            'staff.doctor.exam',
            ['p' => $this->patientOrFail($no)]
        );
    }

    public function storeExam(Request $r, string $no)
    {
        $p = $this->patientOrFail($no);
        $booking = $this->bookingOrFail($no);

        $r->validate([
            'bp' => [
                'required',
                'regex:/^\d{2,3}\/\d{2,3}$/',
            ],
            'pulse' => 'required|integer|between:20,250',
            'temp' => 'required|numeric|between:30,45',
            'height' => 'required|numeric|between:30,250',
            'weight' => 'required|numeric|between:1,400',
            'complaint' => 'required|string',
            'anamnesis' => 'nullable|string',
            'physical' => 'nullable|string',
            'diagnosis' => 'required|string|max:255',
            'icd' => 'nullable|string|max:10',
            'education' => 'nullable|string',
        ], [
            'bp.regex' =>
                'Format tekanan darah: sistolik/diastolik, mis. 120/80.',
        ]);

        $doctorId = Auth::id() ?: $booking->doctor_id;

        $note = trim(
            "Tinggi badan: {$r->height} cm\n" .
            "Pemeriksaan fisik: " . ($r->physical ?? '') . "\n" .
            "Edukasi: " . ($r->education ?? '')
        );
Examination::updateOrCreate(
    ['booking_id' => $booking->id],
    [
        'doctor_id' => $doctorId,
        'complaint' => $r->complaint,
        'bp' => $r->bp,
        'temp' => $r->temp,
        'pulse' => $r->pulse,
        'height' => $r->height,
        'weight' => $r->weight,
        'diagnosis' => $r->diagnosis,
        'note' => $note,
        'anamnesis' => $r->anamnesis,
        'icd' => $r->icd,
        'therapy' => null,
        'education' => $r->education,
    ]
);

        return redirect()
            ->route('doctor.rx.create', $no)
            ->with(
                'status',
                'Pemeriksaan tersimpan. Lanjutkan membuat resep.'
            );
    }

    public function rxCreate(string $no)
    {
        return view('staff.doctor.rx-create', [
            'p' => $this->patientOrFail($no),
            'medicines' => $this->repo->medicines(),
        ]);
    }

    public function rxStore(Request $r, string $no)
    {
        $p = $this->patientOrFail($no);
        $booking = $this->bookingOrFail($no);

        $r->validate([
            'items' => 'required|array|min:1',
            'items.*.medicine' => 'required',
            'items.*.dose' => 'required',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.rule' => 'required',
            'note' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Tambahkan minimal satu obat.',
        ]);

        $meds = collect($this->repo->medicines())->keyBy('name');

        $allergies = $p['allergies'] ?? [];

        if (is_string($allergies)) {
            $allergies = array_filter(
                array_map('trim', explode(',', $allergies))
            );
        }

        foreach ($r->input('items') as $item) {
            $medicine = $meds->get($item['medicine']);

            if (! $medicine) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        "Obat {$item['medicine']} tidak ditemukan."
                    );
            }

            $class = $medicine['class'] ?? null;

            if (
                $class &&
                in_array($class, $allergies, true)
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        "{$item['medicine']} berbenturan dengan alergi {$class} milik pasien."
                    );
            }
        }

        $examination = Examination::query()
            ->where('booking_id', $booking->id)
            ->first();

        if (! $examination) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pemeriksaan belum disimpan.'
                );
        }

        DB::transaction(function () use (
            $r,
            $booking,
            $examination
        ) {
            $prescription = Prescription::create([
                'code' => 'RX-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'examination_id' => $examination->id,
                'status' => 'Menunggu',
                'pharmacy_no' => null,
                'note' => $r->input('note'),
                'handed_by' => null,
                'handed_at' => null,
            ]);

            foreach ($r->input('items') as $item) {
                $medicine = Medicine::query()
                    ->where('name', $item['medicine'])
                    ->firstOrFail();

                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicine->id,
                    'qty' => $item['qty'],
                    'dose' => $item['dose'],
                    'rule' => $item['rule'],
                    'form' => $item['form'] ?? null,
                ]);
            }

            $booking->update([
                'status' => 'done',
                'finished_at' => now(),
            ]);
        });

        return redirect()
            ->route('doctor.queue')
            ->with(
                'status',
                "Resep untuk {$booking->patient->name} dikirim ke apotek."
            );
    }

    public function history(Request $r)
    {
        return view('staff.doctor.history', [
            'rows' => $this->repo->examHistory($r->query('q')),
            'q' => $r->query('q'),
        ]);
    }

    public function schedule(Request $r)
    {
        $doctors = $this->repo->doctors();

        $username = Auth::user()?->username
            ?? session('staff.username');

        $me = collect($doctors)->firstWhere(
            'username',
            $username
        ) ?? collect($doctors)->first();

        abort_unless($me, 404);

        return view('staff.doctor.schedule', [
            'doctor' => $me,
            'sessions' => $this->repo->weekSchedule($me['id']),
            'weekStart' => now()->startOfWeek(),
        ]);
    }
}