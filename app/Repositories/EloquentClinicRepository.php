<?php

namespace App\Repositories;

use App\Contracts\ClinicRepository;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EloquentClinicRepository implements ClinicRepository
{
    private function doctorCode(User $doctor): string
    {
        $codes = [
            'drandi' => 'D-001',
            'drbudi' => 'D-002',
            'drsiti' => 'D-003',
        ];

        return $codes[$doctor->username] ?? 'D-' . str_pad((string) $doctor->id, 3, '0', STR_PAD_LEFT);
    }

    private function resolveDoctor(string $doctorId): ?User
{
    if (preg_match('/^D-(\d{3})$/i', $doctorId, $matches)) {
        $code = strtoupper($doctorId);

        $map = [
            'D-001' => 'drandi',
            'D-002' => 'drbudi',
            'D-003' => 'drsiti',
        ];

        // Untuk kode dokter lama yang punya mapping username
        if (isset($map[$code])) {
            return User::where('username', $map[$code])
                ->where('role', 'doctor')
                ->first();
        }

        // Untuk dokter baru, misalnya D-006 → user ID 6
        $userId = (int) $matches[1];

        return User::where('id', $userId)
            ->where('role', 'doctor')
            ->first();
    }

    return User::where('id', $doctorId)
        ->where('role', 'doctor')
        ->first();
}
    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'checked_in' => 'Check-in',
            'done' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $status ?? '',
        };
    }

    private function formatDate($date): string
    {
        return $date
            ? Carbon::parse($date)->translatedFormat('j F Y')
            : '';
    }

    public function poli(): array
    {
        return Poli::orderBy('name')
            ->get()
            ->map(fn ($poli) => [
                'id' => (string) $poli->id,
                'name' => $poli->name,
            ])
            ->values()
            ->all();
    }

    public function doctors(?string $poli = null): array
    {
        $query = User::with('poli')
            ->where('role', 'doctor')
            ->orderBy('name');

        if ($poli) {
            $query->whereHas('poli', function ($q) use ($poli) {
                $q->where('name', $poli)
                    ->orWhere('id', $poli);
            });
        }

        return $query->get()
            ->map(fn ($doctor) => [
                'id' => $this->doctorCode($doctor),
                'name' => $doctor->name,
                'poli' => $doctor->poli?->name ?? '',
                'sip' => $doctor->sip ?? '',
                'phone' => $doctor->phone ?? '',
                'username' => $doctor->username ?? '',
                'status' => $doctor->status ?? 'Aktif',
            ])
            ->values()
            ->all();
    }

    public function slots(string $doctorId, string $date): array
    {
        $doctor = $this->resolveDoctor($doctorId);

        if (!$doctor) {
            return [];
        }

        $day = Carbon::parse($date)->dayOfWeekIso - 1;

        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day', $day)
            ->first();

        if (!$schedule) {
            return [];
        }

        $start = Carbon::parse($date . ' ' . $schedule->start);
        $end = Carbon::parse($date . ' ' . $schedule->end);

        $result = [];

        while ($start < $end) {
            $time = $start->format('H:i');

            $booked = Booking::where('doctor_id', $doctor->id)
                ->whereDate('date', $date)
                ->where('time', $time)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();

            $quota = $schedule->quota ?? 10;

            $result[] = [
                'time' => $time,
                'available' => $booked < $quota,
                'left' => max(0, $quota - $booked),
            ];

            $start->addHour();
        }

        return $result;
    }

    public function patients(?string $search = null): array
    {
        $query = Patient::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('rm', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn ($patient) => [
                'id' => (string) $patient->id,
                'rm' => $patient->rm,
                'name' => $patient->name,
                'nik' => $patient->nik,
                'gender' => $patient->gender,
                'birth' => $patient->birth?->format('Y-m-d'),
                'age' => $patient->birth
                    ? Carbon::parse($patient->birth)->age
                    : null,
                'phone' => $patient->phone ?? '',
                'address' => $patient->address ?? '',
                'last_visit' => $patient->last_visit ?? null,
            ])
            ->values()
            ->all();
    }

    public function patientByNik(string $nik): ?array
    {
        $patient = Patient::where('nik', $nik)->first();

        if (!$patient) {
            return null;
        }

        return $this->patientProfile((string) $patient->id);
    }

    public function patientProfile(string $patientId): array
    {
        $patient = Patient::findOrFail($patientId);

        return [
            'id' => (string) $patient->id,
            'rm' => $patient->rm,
            'name' => $patient->name,
            'nik' => $patient->nik,
            'gender' => $patient->gender,
            'birth' => $patient->birth?->format('Y-m-d'),
            'age' => $patient->birth
                ? Carbon::parse($patient->birth)->age
                : null,
            'phone' => $patient->phone ?? '',
            'email' => $patient->email ?? '',
            'address' => $patient->address ?? '',
            'blood_type' => $patient->blood_type ?? '',
            'allergies' => $patient->allergies ?? '',
            'emergency' => [
                'name' => $patient->emergency_name ?? '',
                'relation' => $patient->emergency_relation ?? '',
                'phone' => $patient->emergency_phone ?? '',
            ],
            'insurance' => [
                'type' => $patient->insurance_type ?? '',
                'number' => $patient->insurance_number ?? '',
            ],
        ];
    }

    public function queueStats(): array
    {
        $today = now()->toDateString();

        return [
            'total' => Booking::whereDate('date', $today)->count(),
            'waiting' => Booking::whereDate('date', $today)
                ->where('status', 'checked_in')
                ->count(),
            'serving' => Booking::whereDate('date', $today)
                ->where('status', 'serving')
                ->count(),
            'done' => Booking::whereDate('date', $today)
                ->where('status', 'done')
                ->count(),
        ];
    }

    public function findBooking(string $code): ?array
    {
        $booking = Booking::with(['patient', 'doctor', 'poli'])
            ->where('code', $code)
            ->first();

        if (!$booking) {
            return null;
        }

        return [
            'id' => (string) $booking->id,
            'patient_id' => (string) $booking->patient_id,
            'patient' => $booking->patient?->name ?? '',
            'rm' => $booking->patient?->rm ?? '',
            'nik' => $booking->patient?->nik ?? '',
            'birth' => $booking->patient?->birth?->format('Y-m-d'),
            'phone' => $booking->patient?->phone ?? '',
            'gender' => $booking->patient?->gender ?? '',
            'poli' => $booking->poli?->name ?? '',
            'doctor' => $booking->doctor?->name ?? '',
            'queue' => $booking->queue_no,
            'code' => $booking->code,
            'date' => $this->formatDate($booking->date),
            'time' => substr((string) $booking->time, 0, 5),
            'type' => $booking->type,
            'status' => $booking->status,
            'state' => $this->statusLabel($booking->status),
            'complaint' => $booking->complaint ?? '',
        ];
    }

    public function bookings(array $filters = []): array
    {
        $query = Booking::with(['patient', 'doctor', 'poli'])
            ->orderByDesc('date')
            ->orderBy('time');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['poli'])) {
            $query->whereHas('poli', function ($q) use ($filters) {
                $q->where('name', $filters['poli'])
                    ->orWhere('id', $filters['poli']);
            });
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhere('rm', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get()
            ->map(fn ($booking) => [
                'code' => $booking->code,
                'patient' => $booking->patient?->name ?? '',
                'rm' => $booking->patient?->rm ?? '',
                'poli' => $booking->poli?->name ?? '',
                'doctor' => $booking->doctor?->name ?? '',
                'date' => $this->formatDate($booking->date),
                'time' => substr((string) $booking->time, 0, 5),
                'type' => $booking->type,
                'status' => $this->statusLabel($booking->status),
                'complaint' => $booking->complaint ?? '',
            ])
            ->values()
            ->all();
    }

    public function appointmentRequests(): array
    {
        return $this->bookings(['status' => 'pending']);
    }

    public function checkinBooking(string $code): bool
    {
        return DB::transaction(function () use ($code) {
            $booking = Booking::where('code', $code)
                ->lockForUpdate()
                ->first();

            if (!$booking || !in_array($booking->status, ['pending', 'approved'], true)) {
                return false;
            }

            $booking->queue_no = $this->nextQueueNumber(
                (int) $booking->poli_id,
                $booking->date
            );

            $booking->status = 'checked_in';
            $booking->checked_in_at = now();
            $booking->save();

            return true;
        });
    }

    private function nextQueueNumber(int $poliId, $date): string
    {
        $poli = Poli::find($poliId);

        $prefix = 'U';

        if ($poli) {
            $name = strtolower($poli->name);

            if (str_contains($name, 'gigi')) {
                $prefix = 'G';
            } elseif (str_contains($name, 'anak')) {
                $prefix = 'A';
            }
        }

        $last = Booking::where('poli_id', $poliId)
            ->whereDate('date', $date)
            ->whereNotNull('queue_no')
            ->orderByDesc('id')
            ->value('queue_no');

        $number = 0;

        if ($last && preg_match('/(\d+)$/', $last, $match)) {
            $number = (int) $match[1];
        }

        return $prefix . '-' . str_pad((string) ($number + 1), 3, '0', STR_PAD_LEFT);
    }

    public function createPatient(array $data): array
{
    return DB::transaction(function () use ($data) {
        $patient = Patient::withTrashed()
            ->where('nik', $data['nik'])
            ->first();

        if ($patient) {
            if ($patient->trashed()) {
                $patient->restore();
            }

            $patient->update([
                'name' => $data['name'],
                'gender' => $data['gender'] ?? $patient->gender,
                'birth' => $data['birth'] ?? $patient->birth,
                'phone' => $data['phone'] ?? $patient->phone,
                'address' => $data['address'] ?? $patient->address,
            ]);
        } else {
            $patient = Patient::create([
                'rm' => $this->generateRm(),
                'nik' => $data['nik'],
                'name' => $data['name'],
                'gender' => $data['gender'] ?? null,
                'birth' => $data['birth'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
        }

        $doctor = $this->resolveDoctor((string) $data['doctor']);

        if (!$doctor) {
            throw new \RuntimeException('Dokter tidak ditemukan.');
        }

        $poli = Poli::where('name', $data['poli'])->first();

        if (!$poli) {
            throw new \RuntimeException('Poli tidak ditemukan.');
        }

        $booking = $this->createBooking([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => $data['date'],
            'time' => $data['time'],
            'type' => 'walkin',
            'status' => 'checked_in',
        ]);

        return [
            'patient' => $patient,
            'booking' => $booking,
        ];
    });
}

    private function generateRm(): string
    {
        $last = Patient::withTrashed()
            ->where('rm', 'like', 'RM-%')
            ->orderByDesc('id')
            ->value('rm');

        $number = 0;

        if ($last && preg_match('/(\d+)$/', $last, $match)) {
            $number = (int) $match[1];
        }

        return 'RM-' . str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
    }

    public function updatePatient(string $id, array $data): bool
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return false;
        }

        return $patient->update([
            'name' => $data['name'] ?? $patient->name,
            'phone' => $data['phone'] ?? $patient->phone,
            'address' => $data['address'] ?? $patient->address,
        ]);
    }

    public function deletePatient(string $id): bool
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return false;
        }

        return (bool) $patient->delete();
    }

    public function updateBooking(string $code, array $data): bool
    {
        $booking = Booking::where('code', $code)->first();

        if (!$booking) {
            return false;
        }

        $action = $data['action'] ?? null;

        if ($action === 'approve') {
            $booking->status = 'approved';
        } elseif ($action === 'reject') {
            $booking->status = 'rejected';
            $booking->rejection_reason = $data['reason'] ?? null;
        } elseif ($action === 'cancel') {
            $booking->status = 'cancelled';
        } elseif ($action === 'reschedule') {
            if (!empty($data['date'])) {
                $booking->date = $data['date'];
            }

            if (!empty($data['time'])) {
                $booking->time = $data['time'];
            }

            $booking->status = 'pending';
        }

        return $booking->save();
    }

   
    public function createSchedule(array $data): bool
{
    $doctor = $this->resolveDoctor((string) $data['doctor']);

    if (! $doctor) {
        return false;
    }

    DoctorSchedule::updateOrCreate(
        [
            'doctor_id' => $doctor->id,
            'day' => $data['day'],
        ],
        [
            'start' => $data['start'],
            'end' => $data['end'],
            'quota' => $data['quota'] ?? 10,
            'room' => $data['room'] ?? null,
        ]
    );

    return true;
}

   public function createStaff(array $data): array
{
    $poliId = $data['poli_id'] ?? null;

    if (!$poliId && !empty($data['poli'])) {
        $poliId = Poli::where('name', $data['poli'])->value('id');
    }

    $user = User::create([
        'name' => $data['name'],
        'username' => $data['username'],
        'email' => $data['username'] . '@medika.test',
        'password' => Hash::make($data['password'] ?? 'password'),
        'role' => $data['role'],
        'phone' => $data['phone'] ?? null,
        'sip' => $data['sip'] ?? null,
        'poli_id' => $poliId,
    ]);

    return [
        'id' => $data['role'] === 'doctor'
            ? $this->doctorCode($user)
            : (string) $user->id,
        'name' => $user->name,
        'poli' => $user->poli?->name ?? '',
        'sip' => $user->sip ?? '',
        'phone' => $user->phone ?? '',
        'username' => $user->username,
        'status' => $user->status ?? 'Aktif',
    ];
}

    public function updateStaff(string $id, array $data): bool
{
    $user = $this->resolveDoctor($id);

    if (!$user) {
        $user = User::find($id);
    }

    if (!$user) {
        return false;
    }

    $poliId = $data['poli_id'] ?? null;

    if (!$poliId && !empty($data['poli'])) {
        $poliId = Poli::where('name', $data['poli'])->value('id');
    }

    return $user->update([
        'name' => $data['name'] ?? $user->name,
        'phone' => $data['phone'] ?? $user->phone,
        'status' => $data['status'] ?? $user->status,
        'poli_id' => $poliId ?? $user->poli_id,
    ]);
}
    public function deleteStaff(string $id): bool
    {
        $user = $this->resolveDoctor($id);

        if (!$user) {
            $user = User::find($id);
        }

        if (!$user) {
            return false;
        }

        return (bool) $user->delete();
    }

    public function weekSchedule(string $doctorId): array
{
    $doctor = $this->resolveDoctor($doctorId);

    if (!$doctor) {
        return [];
    }

    return DoctorSchedule::query()
        ->where('doctor_id', $doctor->id)
        ->orderBy('day')
        ->get()
        ->map(fn ($schedule) => [
            'day' => (int) $schedule->day,
            'start' => substr((string) $schedule->start, 0, 5),
            'end' => substr((string) $schedule->end, 0, 5),
            'poli' => $doctor->poli?->name ?? '',
            'quota' => $schedule->quota,
            'booked' => 0,
        ])
        ->values()
        ->all();
}

    public function leaveConflicts(string $doctorId, string $date): array
    {
        return [];
    }

    public function patientVisits(string $patientId, ?string $search = null): array
    {
        $query = Examination::with(['booking.doctor', 'booking.poli'])
            ->whereHas('booking', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                    ->orWhere('complaint', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(fn ($exam) => [
                'id' => (string) $exam->id,
                'date' => $this->formatDate($exam->created_at),
                'doctor' => $exam->booking?->doctor?->name ?? '',
                'poli' => $exam->booking?->poli?->name ?? '',
                'complaint' => $exam->complaint ?? '',
                'diagnosis' => $exam->diagnosis ?? '',
                'bp' => $exam->bp ?? '',
                'temp' => $exam->temp ?? '',
                'pulse' => $exam->pulse ?? '',
                'weight' => $exam->weight ?? '',
                'note' => $exam->note ?? '',
                'rx' => '',
            ])
            ->values()
            ->all();
    }

    public function patientPrescriptions(string $patientId): array
    {
        return $this->prescriptionsForPatient($patientId);
    }

    private function prescriptionsForPatient(string $patientId): array
    {
        $prescriptions = Prescription::with([
            'examination.booking.doctor',
            'examination.booking.poli',
            'items.medicine',
        ])
            ->whereHas('examination.booking', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            })
            ->latest()
            ->get();

        return $prescriptions
            ->map(fn ($rx) => $this->mapPrescription($rx))
            ->values()
            ->all();
    }

    private function mapPrescription(Prescription $rx): array
    {
        $booking = $rx->examination?->booking;

        return [
            'code' => $rx->code,
            'patient' => $booking?->patient?->name ?? '',
            'patient_id' => $booking?->patient_id,
            'rm' => $booking?->patient?->rm ?? '',
            'age' => $booking?->patient?->birth
                ? Carbon::parse($booking->patient->birth)->age
                : null,
            'gender' => $booking?->patient?->gender ?? '',
            'dob' => $booking?->patient?->birth?->format('Y-m-d'),
            'allergy' => $booking?->patient?->allergies ?? '',
            'doctor' => $booking?->doctor?->name ?? '',
            'poli' => $booking?->poli?->name ?? '',
            'date' => $this->formatDate($rx->created_at),
            'time' => $rx->created_at?->format('H:i') ?? '',
            'status' => $rx->status,
            'pharmacy_no' => $rx->pharmacy_no,
            'booking' => $booking?->code ?? '',
            'note' => $rx->note ?? '',
            'handed_at' => $rx->handed_at,
            'handed_by' => $rx->handedBy?->name ?? '',
            'items' => $rx->items->map(fn ($item) => [
                'name' => $item->medicine?->name ?? '',
                'qty' => $item->qty,
                'dose' => $item->dose ?? '',
                'rule' => $item->rule ?? '',
                'form' => $item->form ?? $item->medicine?->form ?? '',
            ])->values()->all(),
        ];
    }

    public function queueStatus(string $ticketCode): array
    {
        $booking = Booking::where('code', $ticketCode)->first();

        if (!$booking) {
            return [
                'serving' => null,
                'ahead' => 0,
                'eta' => 0,
                'progress' => 0,
            ];
        }

        $ahead = Booking::where('poli_id', $booking->poli_id)
            ->whereDate('date', $booking->date)
            ->where('status', 'checked_in')
            ->where('queue_no', '<', $booking->queue_no)
            ->count();

        return [
            'serving' => Booking::where('poli_id', $booking->poli_id)
                ->whereDate('date', $booking->date)
                ->where('status', 'serving')
                ->orderBy('queue_no')
                ->value('queue_no'),
            'ahead' => $ahead,
            'eta' => $ahead * 10,
            'progress' => $ahead === 0 ? 100 : max(0, 100 - ($ahead * 10)),
        ];
    }

    public function tickets(string $patientId): array
    {
        return Booking::with(['patient', 'doctor', 'poli'])
            ->where('patient_id', $patientId)
            ->latest()
            ->get()
            ->map(fn ($booking) => [
                'code' => $booking->code,
                'queue' => $booking->queue_no,
                'poli' => $booking->poli?->name ?? '',
                'doctor' => $booking->doctor?->name ?? '',
                'date' => $this->formatDate($booking->date),
                'time' => substr((string) $booking->time, 0, 5),
                'status' => $this->statusLabel($booking->status),
                'name' => $booking->patient?->name ?? '',
            ])
            ->values()
            ->all();
    }

    public function ticket(string $code): ?array
    {
        $booking = Booking::with(['patient', 'doctor', 'poli'])
            ->where('code', $code)
            ->first();

        if (!$booking) {
            return null;
        }

        return [
            'code' => $booking->code,
            'patient_id' => (string) $booking->patient_id,
            'patient' => $booking->patient?->name ?? '',
            'rm' => $booking->patient?->rm ?? '',
            'nik' => $booking->patient?->nik ?? '',
            'doctor' => $booking->doctor?->name ?? '',
            'poli' => $booking->poli?->name ?? '',
            'date' => $this->formatDate($booking->date),
            'time' => substr((string) $booking->time, 0, 5),
            'queue_no' => $booking->queue_no,
            'queue' => $booking->queue_no,
            'status' => $this->statusLabel($booking->status),
            'complaint' => $booking->complaint ?? '',
        ];
    }

    public function hasActiveTicket(string $patientId, string $poli): bool
    {
        return Booking::where('patient_id', $patientId)
            ->whereHas('poli', fn ($q) => $q->where('name', $poli))
            ->whereIn('status', ['pending', 'approved', 'checked_in'])
            ->exists();
    }

    public function queue(array $filters = []): array
    {
        $query = Booking::with(['patient', 'doctor', 'poli'])
            ->whereDate('date', now()->toDateString())
            ->orderBy('queue_no');

        if (!empty($filters['poli'])) {
            $query->whereHas('poli', fn ($q) => $q->where('name', $filters['poli']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];

            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('rm', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(fn ($booking) => [
                'no' => $booking->queue_no,
                'patient' => $booking->patient?->name ?? '',
                'doctor' => $booking->doctor?->name ?? '',
                'poli' => $booking->poli?->name ?? '',
                'time' => substr((string) $booking->time, 0, 5),
                'status' => $this->statusLabel($booking->status),
            ])
            ->values()
            ->all();
    }

    public function doctorQueue(): array
    {
        $doctor = User::find(auth()->id());

        if (!$doctor || $doctor->role !== 'doctor') {
            $doctor = User::where('role', 'doctor')->first();
        }

        if (!$doctor) {
            return [];
        }

        return Booking::with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', now()->toDateString())
            ->whereIn('status', ['checked_in', 'serving', 'done', 'cancelled'])
            ->orderBy('queue_no')
            ->get()
            ->map(fn ($booking) => [
                'no' => $booking->queue_no,
                'patient' => $booking->patient?->name ?? '',
                'rm' => $booking->patient?->rm ?? '',
                'age' => $booking->patient?->birth
                    ? Carbon::parse($booking->patient->birth)->age
                    : null,
                'gender' => $booking->patient?->gender ?? '',
                'dob' => $booking->patient?->birth?->format('Y-m-d'),
                'nik' => $booking->patient?->nik ?? '',
                'complaint' => $booking->complaint ?? '',
                'registered' => $booking->checked_in_at?->format('H:i') ?? '',
                'status' => match ($booking->status) {
    'checked_in' => 'Menunggu',
    'serving' => 'Dipanggil',
    'done' => 'Selesai',
    'cancelled' => 'Tidak Hadir',
    default => $this->statusLabel($booking->status),
},
                'allergies' => $booking->patient?->allergies ?? '',
                'allergy_reaction' => '',
            ])
            ->values()
            ->all();
    }

    public function recordVisits(string $rm): array
    {
        $patient = Patient::where('rm', $rm)->first();

        if (!$patient) {
            return [];
        }

        return $this->patientVisits((string) $patient->id);
    }

    public function examHistory(?string $search = null): array
    {
        $query = Examination::with(['booking.patient', 'booking.doctor'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('booking.patient', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhere('rm', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get()
            ->map(fn ($exam) => [
                'id' => (string) $exam->id,
                'date' => $this->formatDate($exam->created_at),
                'patient' => $exam->booking?->patient?->name ?? '',
                'complaint' => $exam->complaint ?? '',
                'diagnosis' => $exam->diagnosis ?? '',
                'bp' => $exam->bp ?? '',
                'note' => $exam->note ?? '',
                'rx' => '',
            ])
            ->values()
            ->all();
    }

    public function pharmacyAlerts(): array
    {
        return Medicine::whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get()
            ->map(fn ($medicine) => [
                'code' => $medicine->code,
                'medicine' => $medicine->name,
                'name' => $medicine->name,
                'stock' => $medicine->stock,
                'min' => $medicine->min_stock,
                'min_stock' => $medicine->min_stock,
                'note' => 'Stok menipis',
            ])
            ->values()
            ->all();
    }

    public function prescriptions(?string $status = null): array
    {
        $query = Prescription::with([
            'examination.booking.patient',
            'examination.booking.doctor',
            'examination.booking.poli',
            'items.medicine',
            'handedBy',
        ])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()
            ->map(fn ($rx) => $this->mapPrescription($rx))
            ->values()
            ->all();
    }

    public function prescription(string $code): ?array
    {
        $rx = Prescription::with([
            'examination.booking.patient',
            'examination.booking.doctor',
            'examination.booking.poli',
            'items.medicine',
            'handedBy',
        ])
            ->where('code', $code)
            ->first();

        return $rx ? $this->mapPrescription($rx) : null;
    }

    public function medicines(?string $search = null): array
    {
        $query = Medicine::query()->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(fn ($medicine) => [
                'id' => (string) $medicine->id,
                'code' => $medicine->code,
                'name' => $medicine->name,
                'category' => $medicine->category ?? '',
                'unit' => $medicine->unit ?? '',
                'price' => $medicine->price,
                'stock' => $medicine->stock,
                'min' => $medicine->min_stock,
                'min_stock' => $medicine->min_stock,
                'expiry' => $medicine->expiry?->format('Y-m-d'),
                'class' => $medicine->class ?? '',
                'form' => $medicine->form ?? '',
            ])
            ->values()
            ->all();
    }

    public function staff(string $role): array
    {
        return User::with('poli')
            ->where('role', $role)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $role === 'doctor'
                    ? $this->doctorCode($user)
                    : (string) $user->id,
                'name' => $user->name,
                'poli' => $user->poli?->name ?? '',
                'sip' => $user->sip ?? '',
                'phone' => $user->phone ?? '',
                'username' => $user->username ?? '',
                'status' => $user->status ?? 'Aktif',
            ])
            ->values()
            ->all();
    }

    public function report(): array
    {
        $today = now()->toDateString();

        return [
            'summary' => [
                'visits' => Booking::whereDate('date', $today)
                    ->where('status', 'done')
                    ->count(),
                'new_patients' => Patient::whereDate('created_at', $today)->count(),
                'prescriptions' => Prescription::whereDate('created_at', $today)->count(),
                'cancel_rate' => 0,
            ],
            'monthly' => [],
            'by_poli' => Poli::withCount([
    'bookings as visits' => function ($q) use ($today) {
        $q->whereDate('date', $today);
    },

    'bookings as done' => function ($q) use ($today) {
        $q->whereDate('date', $today)
            ->where('status', 'done');
    },

    'bookings as cancel' => function ($q) use ($today) {
        $q->whereDate('date', $today)
            ->whereIn('status', ['cancelled', 'canceled']);
    },
])->get()->map(fn ($poli) => [
    'poli' => $poli->name,
    'visits' => $poli->visits,
    'done' => $poli->done,
    'cancel' => $poli->cancel,
])->values()->all(),
        ];
    }

    public function dashboard(): array
{
    $today = now()->toDateString();
    $day = Carbon::parse($today)->dayOfWeekIso;

    $schedules = DoctorSchedule::with(['doctor.poli'])
        ->where('day', $day)
        ->orderBy('start')
        ->get();

    return [
        'stats' => [
            'patients_today' => Booking::whereDate('date', $today)
                ->distinct('patient_id')
                ->count('patient_id'),

            'doctors_on_duty' => $schedules
                ->pluck('doctor_id')
                ->unique()
                ->count(),

            'prescriptions_pending' => Prescription::whereIn(
                'status',
                ['Menunggu', 'Diproses']
            )->count(),

            'new_patients' => Patient::whereDate(
                'created_at',
                $today
            )->count(),
        ],

        'today' => $schedules->map(fn ($schedule) => [
            'doctor' => $schedule->doctor?->name ?? '',
            'poli' => $schedule->doctor?->poli?->name ?? '',
            'room' => $schedule->room ?? '-',
            'start' => substr((string) $schedule->start, 0, 5),
            'end' => substr((string) $schedule->end, 0, 5),
            'quota' => $schedule->quota,
        ])->values()->all(),
    ];
}

    public function createBooking(array $data): array
{
    return DB::transaction(function () use ($data) {
        $patient = Patient::findOrFail($data['patient_id']);
        $doctor = $this->resolveDoctor((string) $data['doctor_id']);
        $poli = Poli::findOrFail($data['poli_id']);

        if (!$doctor) {
            throw new \RuntimeException('Dokter tidak ditemukan.');
        }

        $booking = Booking::create([
            'code' => $this->generateBookingCode($data['date']),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'poli_id' => $poli->id,
            'date' => $data['date'],
            'time' => $data['time'],
            'type' => $data['type'] ?? 'online',
            'status' => $data['status'] ?? 'pending',
            'complaint' => $data['complaint'] ?? null,
        ]);

        // Semua booking mendapatkan nomor antrean
        $booking->queue_no = $this->nextQueueNumber(
            $poli->id,
            $booking->date
        );

        $booking->save();

        return $this->findBooking($booking->code);
    });
}
    private function generateBookingCode($date): string
    {
        $prefix = 'BK-' . Carbon::parse($date)->format('Ymd') . '-';

        $last = Booking::where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $number = 0;

        if ($last && preg_match('/(\d+)$/', $last, $match)) {
            $number = (int) $match[1];
        }

        return $prefix . str_pad((string) ($number + 1), 3, '0', STR_PAD_LEFT);
    }
}