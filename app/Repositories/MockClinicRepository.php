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
        $map = [
            'drandi' => 'D-001',
            'drbudi' => 'D-002',
            'drsiti' => 'D-003',
        ];

        return $map[$doctor->username]
            ?? 'D-' . str_pad((string) $doctor->id, 3, '0', STR_PAD_LEFT);
    }

    private function resolveDoctor(string $doctorId): ?User
    {
        if (preg_match('/^D-\d{3}$/i', $doctorId)) {
            $map = [
                'D-001' => 'drandi',
                'D-002' => 'drbudi',
                'D-003' => 'drsiti',
            ];

            $username = $map[strtoupper($doctorId)] ?? null;

            if ($username) {
                return User::query()
                    ->where('username', $username)
                    ->where('role', 'doctor')
                    ->first();
            }
        }

        return User::query()
            ->where('id', $doctorId)
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
            default => (string) $status,
        };
    }

    private function doctorStatus(User $doctor): string
    {
        return $doctor->status ?? 'Aktif';
    }

    private function formatDate(string|\DateTimeInterface|null $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->translatedFormat('j F Y');
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' .
                str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        } while (Booking::query()->where('code', $code)->exists());

        return $code;
    }

    private function generatePrescriptionCode(): string
    {
        do {
            $code = 'RX-' . now()->format('YmdHis') . '-' .
                random_int(100, 999);
        } while (Prescription::query()->where('code', $code)->exists());

        return $code;
    }

    private function nextQueueNumber(
        int $poliId,
        string $date
    ): string {
        $prefix = 'U';

        $poli = Poli::query()->find($poliId);

        if ($poli) {
            $name = strtolower($poli->name);

            if (str_contains($name, 'gigi')) {
                $prefix = 'G';
            } elseif (str_contains($name, 'anak')) {
                $prefix = 'A';
            } elseif (
                str_contains($name, 'dalam')
                || str_contains($name, 'penyakit')
            ) {
                $prefix = 'D';
            }
        }

        $last = Booking::query()
            ->where('poli_id', $poliId)
            ->whereDate('date', $date)
            ->whereNotNull('queue_no')
            ->orderByDesc('id')
            ->value('queue_no');

        $number = 0;

        if ($last && preg_match('/(\d+)$/', $last, $match)) {
            $number = (int) $match[1];
        }

        return $prefix . str_pad(
            (string) ($number + 1),
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    public function poli(): array
    {
        return Poli::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => (string) $p->id,
                'name' => $p->name,
            ])
            ->all();
    }

    public function doctors(?string $poli = null): array
    {
        $query = User::query()
            ->where('role', 'doctor')
            ->with('poli')
            ->orderBy('name');

        if ($poli) {
            $query->whereHas(
                'poli',
                fn ($q) => $q->where('name', $poli)
            );
        }

        return $query->get()
            ->map(fn (User $doctor) => [
                'id' => $this->doctorCode($doctor),
                'name' => $doctor->name,
                'poli' => $doctor->poli?->name ?? '-',
                'sip' => $doctor->sip ?? '-',
                'phone' => $doctor->phone ?? '-',
                'username' => $doctor->username,
                'status' => $this->doctorStatus($doctor),
            ])
            ->all();
    }

    public function slots(string $doctorId, string $date): array
    {
        $doctor = $this->resolveDoctor($doctorId);

        if (! $doctor) {
            return [];
        }

        $day = Carbon::parse($date)->dayOfWeekIso - 1;

        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->where('day', $day)
            ->first();

        if (! $schedule) {
            return [];
        }

        $start = Carbon::createFromFormat(
            'H:i',
            substr($schedule->start, 0, 5)
        );

        $end = Carbon::createFromFormat(
            'H:i',
            substr($schedule->end, 0, 5)
        );

        $slots = [];

        while ($start < $end) {
            $time = $start->format('H:i');

            $booked = Booking::query()
                ->where('doctor_id', $doctor->id)
                ->whereDate('date', $date)
                ->where('time', 'like', $time . '%')
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();

            $left = max(
                0,
                (int) $schedule->quota - $booked
            );

            $slots[] = [
                'time' => $time,
                'available' => $left > 0,
                'left' => $left,
            ];

            $start->addHour();
        }

        return $slots;
    }

    public function patients(?string $search = null): array
    {
        $query = Patient::query()
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('rm', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'rm' => $p->rm,
                'name' => $p->name,
                'nik' => $p->nik,
                'gender' => $p->gender,
                'birth' => $p->birth,
                'age' => $p->birth
                    ? Carbon::parse($p->birth)->age
                    : null,
                'phone' => $p->phone,
                'address' => $p->address,
                'last_visit' => $this->formatDate(
                    Booking::query()
                        ->where('patient_id', $p->id)
                        ->where('status', 'done')
                        ->max('date')
                ),
            ])
            ->all();
    }

    public function patientByNik(string $nik): ?array
    {
        $patient = Patient::query()
            ->where('nik', $nik)
            ->first();

        if (! $patient) {
            return null;
        }

        return collect($this->patients())
            ->firstWhere('nik', $nik);
    }

    public function patientProfile(string $patientId): array
    {
        $patient = Patient::query()
            ->findOrFail($patientId);

        return [
            'id' => (string) $patient->id,
            'rm' => $patient->rm,
            'name' => $patient->name,
            'nik' => $patient->nik,
            'gender' => $patient->gender,
            'birth' => $patient->birth,
            'age' => $patient->birth
                ? Carbon::parse($patient->birth)->age
                : null,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'address' => $patient->address,
            'blood_type' => $patient->blood_type,
            'allergies' => $patient->allergies,
            'emergency' => [
                'name' => $patient->emergency_name,
                'relation' => $patient->emergency_relation,
                'phone' => $patient->emergency_phone,
            ],
            'insurance' => [
                'type' => $patient->insurance_type,
                'number' => $patient->insurance_number,
            ],
        ];
    }

    public function patientVisits(
        string $patientId,
        ?string $search = null
    ): array {
        $query = Examination::query()
            ->with([
                'booking.doctor',
                'booking.poli',
                'prescription.items.medicine',
            ])
            ->whereHas(
                'booking',
                fn ($q) => $q->where('patient_id', $patientId)
            )
            ->latest();

        $rows = $query->get()
            ->map(function (Examination $exam) {
                $booking = $exam->booking;

                return [
                    'id' => 'PX-' . $exam->id,
                    'date' => $this->formatDate($booking?->date),
                    'time' => $booking?->time ?? '-',
                    'doctor' => $booking?->doctor?->name ?? '-',
                    'poli' => $booking?->poli?->name ?? '-',
                    'complaint' => $exam->complaint ?? '-',
                    'diagnosis' => $exam->diagnosis ?? '-',
                    'bp' => $exam->bp ?? '-',
                    'temp' => $exam->temp ?? '-',
                    'pulse' => $exam->pulse ?? '-',
                    'weight' => $exam->weight ?? '-',
                    'height' => null,
                    'note' => $exam->note ?? '',
                    'anamnesis' => $exam->anamnesis ?? '',
                    'icd' => $exam->icd ?? '',
                    'therapy' => $exam->therapy ?? '',
                    'education' => '',
                    'status' => 'Selesai',
                    'rx' => $exam->prescription?->code,
                    'meds' => $exam->prescription
                        ? $exam->prescription->items
                            ->map(fn ($item) => [
                                'name' => $item->medicine?->name,
                                'qty' => $item->qty,
                                'dose' => $item->dose,
                                'rule' => $item->rule,
                                'form' => $item->form,
                            ])
                            ->all()
                        : [],
                ];
            })
            ->all();

        if ($search) {
            $search = strtolower($search);

            $rows = array_values(
                array_filter(
                    $rows,
                    fn ($row) =>
                    str_contains(
                        strtolower(
                            ($row['diagnosis'] ?? '') .
                            ($row['doctor'] ?? '') .
                            ($row['poli'] ?? '')
                        ),
                        $search
                    )
                )
            );
        }

        return $rows;
    }

    public function patientPrescriptions(
        string $patientId
    ): array {
        return $this->prescriptionsForPatient($patientId);
    }

    private function prescriptionsForPatient(
        string $patientId
    ): array {
        return Prescription::query()
            ->with([
                'examination.booking.doctor',
                'examination.booking.poli',
                'items.medicine',
                'handedBy',
            ])
            ->whereHas(
                'examination.booking',
                fn ($q) => $q->where(
                    'patient_id',
                    $patientId
                )
            )
            ->latest()
            ->get()
            ->map(
                fn (Prescription $rx) =>
                $this->mapPrescription($rx)
            )
            ->all();
    }

    public function queueStatus(string $ticketCode): array
    {
        $booking = Booking::query()
            ->where('code', $ticketCode)
            ->firstOrFail();

        $current = Booking::query()
            ->where('doctor_id', $booking->doctor_id)
            ->where('poli_id', $booking->poli_id)
            ->whereDate('date', $booking->date)
            ->where('status', 'checked_in')
            ->whereNotNull('queue_no')
            ->orderBy('queue_no')
            ->first();

        $ahead = 0;

        if ($booking->queue_no) {
            $ahead = Booking::query()
                ->where('doctor_id', $booking->doctor_id)
                ->where('poli_id', $booking->poli_id)
                ->whereDate('date', $booking->date)
                ->where('status', 'checked_in')
                ->whereNotNull('queue_no')
                ->where('queue_no', '<', $booking->queue_no)
                ->count();
        }

        return [
            'serving' => $current?->queue_no ?? '-',
            'ahead' => $ahead,
            'eta' => $ahead > 0
                ? 'sekitar ' . ($ahead * 10) . ' menit'
                : 'sebentar lagi',
            'progress' => max(
                0,
                min(100, 100 - ($ahead * 10))
            ),
        ];
    }

    public function tickets(string $patientId): array
    {
        return Booking::query()
            ->with(['doctor', 'poli', 'patient'])
            ->where('patient_id', $patientId)
            ->latest('date')
            ->latest('time')
            ->get()
            ->map(fn (Booking $b) => [
                'code' => $b->code,
                'queue' => $b->queue_no,
                'poli' => $b->poli?->name ?? '-',
                'doctor' => $b->doctor?->name ?? '-',
                'date' => $this->formatDate($b->date),
                'time' => $b->time,
                'status' => match ($b->status) {
                    'pending' => 'Menunggu Persetujuan',
                    'approved', 'checked_in' => 'Aktif',
                    'done' => 'Selesai',
                    'cancelled', 'rejected' => 'Batal',
                    default => $b->status,
                },
                'name' => $b->patient?->name,
            ])
            ->all();
    }

    public function ticket(string $code): ?array
    {
        $booking = Booking::query()
            ->with(['doctor', 'poli', 'patient'])
            ->where('code', $code)
            ->first();

        if (! $booking) {
            return null;
        }

        return [
            'code' => $booking->code,
            'patient_id' => (string) $booking->patient_id,
            'patient' => $booking->patient?->name,
            'rm' => $booking->patient?->rm,
            'nik' => $booking->patient?->nik,
            'doctor' => $booking->doctor?->name,
            'poli' => $booking->poli?->name,
            'date' => $this->formatDate($booking->date),
            'time' => $booking->time,
            'queue_no' => $booking->queue_no,
            'queue' => $booking->queue_no,
            'status' => match ($booking->status) {
                'pending' => 'Menunggu Persetujuan',
                'approved', 'checked_in' => 'Aktif',
                'done' => 'Selesai',
                'cancelled', 'rejected' => 'Batal',
                default => $booking->status,
            },
            'complaint' => $booking->complaint,
        ];
    }

    public function hasActiveTicket(
        string $patientId,
        string $poli
    ): bool {
        $poliModel = Poli::query()
            ->where('name', $poli)
            ->first();

        if (! $poliModel) {
            return false;
        }

        return Booking::query()
            ->where('patient_id', $patientId)
            ->where('poli_id', $poliModel->id)
            ->whereIn('status', [
                'pending',
                'approved',
                'checked_in',
            ])
            ->exists();
    }

    public function queueStats(): array
    {
        $today = now()->toDateString();

        $query = Booking::query()
            ->whereDate('date', $today);

        return [
            'total' => (clone $query)->count(),
            'waiting' => (clone $query)
                ->whereIn('status', [
                    'pending',
                    'approved',
                    'checked_in',
                ])
                ->count(),
            'serving' => (clone $query)
                ->where('status', 'checked_in')
                ->whereNotNull('started_at')
                ->count(),
            'done' => (clone $query)
                ->where('status', 'done')
                ->count(),
        ];
    }

    public function queue(array $f = []): array
    {
        $query = Booking::query()
            ->with(['patient', 'doctor', 'poli'])
            ->whereDate('date', now()->toDateString())
            ->orderBy('time');

        if (! empty($f['poli'])) {
            $query->whereHas(
                'poli',
                fn ($q) => $q->where('name', $f['poli'])
            );
        }

        if (! empty($f['q'])) {
            $q = $f['q'];

            $query->whereHas('patient', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('rm', 'like', "%{$q}%");
            });
        }

        $rows = $query->get()
            ->map(function (Booking $b) {
                $status = match ($b->status) {
                    'checked_in' => $b->started_at
                        ? 'Sedang Diperiksa'
                        : 'Menunggu',
                    'done' => 'Selesai',
                    'cancelled' => $b->rejection_reason === 'Tidak Hadir'
                        ? 'Tidak Hadir'
                        : 'Batal',
                    'approved' => 'Disetujui',
                    'pending' => 'Menunggu Persetujuan',
                    'rejected' => 'Ditolak',
                    default => $b->status,
                };

                return [
                    'no' => $b->queue_no ?? '-',
                    'patient' => $b->patient?->name ?? '-',
                    'doctor' => $b->doctor?->name ?? '-',
                    'poli' => $b->poli?->name ?? '-',
                    'time' => $b->time,
                    'status' => $status,
                ];
            })
            ->all();

        if (! empty($f['status'])) {
            $rows = array_values(
                array_filter(
                    $rows,
                    fn ($row) =>
                    $row['status'] === $f['status']
                )
            );
        }

        return $rows;
    }

    public function findBooking(string $code): ?array
    {
        $b = Booking::query()
            ->with(['patient', 'doctor', 'poli'])
            ->where('code', $code)
            ->first();

        if (! $b) {
            return null;
        }

        return [
            'id' => $b->id,
            'patient_id' => $b->patient_id,
            'patient' => $b->patient?->name,
            'rm' => $b->patient?->rm,
            'nik' => $b->patient?->nik,
            'birth' => $b->patient?->birth,
            'phone' => $b->patient?->phone,
            'gender' => $b->patient?->gender,
            'poli' => $b->poli?->name,
            'doctor' => $b->doctor?->name,
            'queue' => $b->queue_no,
            'code' => $b->code,
            'date' => $this->formatDate($b->date),
            'time' => $b->time,
            'type' => ucfirst($b->type),
            'status' => $this->statusLabel($b->status),
            'state' => $b->status,
            'complaint' => $b->complaint,
        ];
    }

    public function bookings(array $f = []): array
    {
        $query = Booking::query()
            ->with(['patient', 'doctor', 'poli'])
            ->latest();

        if (! empty($f['status'])) {
            $statusMap = [
                'Menunggu Persetujuan' => 'pending',
                'Disetujui' => 'approved',
                'Check-in' => 'checked_in',
                'Selesai' => 'done',
                'Dibatalkan' => 'cancelled',
                'Ditolak' => 'rejected',
            ];

            $query->where(
                'status',
                $statusMap[$f['status']] ?? $f['status']
            );
        }

        if (! empty($f['poli'])) {
            $query->whereHas(
                'poli',
                fn ($q) => $q->where('name', $f['poli'])
            );
        }

        if (! empty($f['q'])) {
            $q = $f['q'];

            $query->where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhereHas(
                        'patient',
                        fn ($p) => $p->where(
                            'name',
                            'like',
                            "%{$q}%"
                        )
                    );
            });
        }

        return $query->get()
            ->map(fn (Booking $b) => [
                'code' => $b->code,
                'patient' => $b->patient?->name,
                'rm' => $b->patient?->rm,
                'poli' => $b->poli?->name,
                'doctor' => $b->doctor?->name,
                'date' => $this->formatDate($b->date),
                'time' => $b->time,
                'type' => ucfirst($b->type),
                'status' => $this->statusLabel($b->status),
                'complaint' => $b->complaint,
            ])
            ->all();
    }

    public function appointmentRequests(): array
    {
        return Booking::query()
            ->with(['patient', 'poli'])
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'patient' => $b->patient?->name,
                'age' => $b->patient?->birth
                    ? Carbon::parse($b->patient->birth)->age
                    : null,
                'poli' => $b->poli?->name,
                'date' => $this->formatDate($b->date),
                'time' => $b->time,
                'complaint' => $b->complaint,
                'status' => 'Menunggu',
            ])
            ->all();
    }

    public function doctorQueue(): array
    {
        $doctor = User::query()
            ->where('id', auth()->id())
            ->where('role', 'doctor')
            ->first();

        if (! $doctor) {
            $doctor = User::query()
                ->where('role', 'doctor')
                ->first();
        }

        if (! $doctor) {
            return [];
        }

        return Booking::query()
            ->with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', now()->toDateString())
            ->whereIn('status', [
                'checked_in',
                'done',
                'cancelled',
            ])
            ->orderByRaw(
                "CASE WHEN queue_no IS NULL THEN 999999 ELSE CAST(SUBSTRING(queue_no, 2) AS UNSIGNED) END"
            )
            ->get()
            ->map(function (Booking $b) {
                $status = match ($b->status) {
                    'checked_in' => $b->started_at
                        ? 'Dipanggil'
                        : 'Menunggu',
                    'done' => 'Selesai',
                    'cancelled' => $b->rejection_reason === 'Tidak Hadir'
                        ? 'Tidak Hadir'
                        : 'Batal',
                    default => $b->status,
                };

                $allergies = [];

                if ($b->patient?->allergies) {
                    $allergies = array_filter(
                        array_map(
                            'trim',
                            explode(',', $b->patient->allergies)
                        )
                    );
                }

                return [
                    'no' => $b->queue_no,
                    'code' => $b->code,
                    'patient' => $b->patient?->name,
                    'rm' => $b->patient?->rm,
                    'age' => $b->patient?->birth
                        ? Carbon::parse(
                            $b->patient->birth
                        )->age
                        : null,
                    'gender' => $b->patient?->gender,
                    'dob' => $b->patient?->birth,
                    'nik' => $b->patient?->nik,
                    'complaint' => $b->complaint,
                    'registered' => $b->time,
                    'status' => $status,
                    'allergies' => $allergies,
                    'allergy_reaction' => null,
                ];
            })
            ->all();
    }

    public function recordVisits(string $rm): array
    {
        $patient = Patient::query()
            ->where('rm', $rm)
            ->first();

        if (! $patient) {
            return [];
        }

        return $this->patientVisits(
            (string) $patient->id
        );
    }

    public function examHistory(?string $search = null): array
    {
        $query = Examination::query()
            ->with([
                'booking.patient',
                'booking.doctor',
            ])
            ->latest();

        if ($search) {
            $query->whereHas(
                'booking.patient',
                fn ($q) =>
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
            );
        }

        return $query->get()
            ->map(fn (Examination $exam) => [
                'id' => 'PX-' . $exam->id,
                'date' => $this->formatDate(
                    $exam->booking?->date
                ),
                'patient' => $exam->booking?->patient?->name,
                'complaint' => $exam->complaint,
                'diagnosis' => $exam->diagnosis,
                'bp' => $exam->bp,
                'note' => $exam->note,
                'rx' => $exam->prescription?->code,
            ])
            ->all();
    }

    public function pharmacyAlerts(): array
    {
        return Medicine::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->get()
            ->map(fn (Medicine $m) => [
                'code' => $m->code,
                'medicine' => $m->name,
                'name' => $m->name,
                'stock' => $m->stock,
                'min' => $m->min_stock,
                'note' => "Stok {$m->name} sudah mencapai batas minimum.",
            ])
            ->all();
    }

    public function prescriptions(
        ?string $status = null
    ): array {
        $query = Prescription::query()
            ->with([
                'examination.booking.patient',
                'examination.booking.doctor',
                'examination.booking.poli',
                'items.medicine',
                'handedBy',
            ])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()
            ->map(
                fn (Prescription $rx) =>
                $this->mapPrescription($rx)
            )
            ->all();
    }

    private function mapPrescription(
        Prescription $rx
    ): array {
        $exam = $rx->examination;
        $booking = $exam?->booking;

        return [
            'code' => $rx->code,
            'patient' => $booking?->patient?->name,
            'patient_id' => $booking?->patient_id,
            'rm' => $booking?->patient?->rm,
            'age' => $booking?->patient?->birth
                ? Carbon::parse(
                    $booking->patient->birth
                )->age
                : null,
            'gender' => $booking?->patient?->gender,
            'dob' => $booking?->patient?->birth,
            'allergy' => $booking?->patient?->allergies,
            'doctor' => $booking?->doctor?->name,
            'poli' => $booking?->poli?->name,
            'date' => $this->formatDate(
                $booking?->date
            ),
            'time' => $booking?->time,
            'status' => $rx->status,
            'pharmacy_no' => $rx->pharmacy_no,
            'booking' => $booking?->code,
            'note' => $rx->note,
            'handed_at' => $rx->handed_at
                ? Carbon::parse(
                    $rx->handed_at
                )->translatedFormat('j F Y, H:i')
                : null,
            'handed_by' => $rx->handedBy?->name,
            'items' => $rx->items
                ->map(fn (PrescriptionItem $item) => [
                    'name' => $item->medicine?->name,
                    'medicine_id' => $item->medicine_id,
                    'qty' => $item->qty,
                    'dose' => $item->dose,
                    'rule' => $item->rule,
                    'form' => $item->form
                        ?? $item->medicine?->form
                        ?? '-',
                    'stock' => $item->medicine?->stock ?? 0,
                ])
                ->all(),
        ];
    }

    public function prescription(
        string $code
    ): ?array {
        $rx = Prescription::query()
            ->with([
                'examination.booking.patient',
                'examination.booking.doctor',
                'examination.booking.poli',
                'items.medicine',
                'handedBy',
            ])
            ->where('code', $code)
            ->first();

        return $rx
            ? $this->mapPrescription($rx)
            : null;
    }

    public function medicines(
        ?string $search = null
    ): array {
        $query = Medicine::query()
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        return $query->get()
            ->map(fn (Medicine $m) => [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'category' => $m->category,
                'unit' => $m->unit,
                'price' => $m->price,
                'stock' => $m->stock,
                'min' => $m->min_stock,
                'min_stock' => $m->min_stock,
                'expiry' => $m->expiry,
                'class' => $m->class,
                'form' => $m->form,
            ])
            ->all();
    }

    public function staff(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->with('poli')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $role === 'doctor'
                    ? $this->doctorCode($user)
                    : (string) $user->id,
                'name' => $user->name,
                'poli' => $user->poli?->name,
                'sip' => $user->sip,
                'phone' => $user->phone,
                'username' => $user->username,
                'status' => $this->doctorStatus($user),
            ])
            ->all();
    }

    public function report(): array
    {
        $visits = Examination::query()->count();
        $patients = Patient::query()->count();
        $prescriptions = Prescription::query()->count();

        $cancelled = Booking::query()
            ->where('status', 'cancelled')
            ->count();

        $totalBookings = Booking::query()->count();

        $cancelRate = $totalBookings > 0
            ? number_format(
                ($cancelled / $totalBookings) * 100,
                1,
                ',',
                ''
            ) . '%'
            : '0%';

        return [
            'summary' => [
                'visits' => $visits,
                'new_patients' => $patients,
                'prescriptions' => $prescriptions,
                'cancel_rate' => $cancelRate,
            ],
            'monthly' => [],
            'by_poli' => Poli::query()
                ->get()
                ->map(function (Poli $poli) {
                    $bookings = Booking::query()
                        ->where('poli_id', $poli->id);

                    return [
                        'poli' => $poli->name,
                        'visits' => (clone $bookings)->count(),
                        'done' => (clone $bookings)
                            ->where('status', 'done')
                            ->count(),
                        'cancel' => (clone $bookings)
                            ->where('status', 'cancelled')
                            ->count(),
                    ];
                })
                ->all(),
        ];
    }

    public function dashboard(): array
    {
        $today = now()->toDateString();

        $patientsToday = Booking::query()
            ->whereDate('date', $today)
            ->count();

        $day = now()->dayOfWeekIso - 1;

        $doctorsOnDuty = User::query()
            ->where('role', 'doctor')
            ->whereHas(
                'doctorSchedules',
                fn ($q) => $q->where('day', $day)
            )
            ->count();

        $prescriptionsPending = Prescription::query()
            ->whereIn('status', [
                'Menunggu',
                'Diproses',
            ])
            ->count();

        $newPatients = Patient::query()
            ->whereDate(
                'created_at',
                $today
            )
            ->count();

        $todayDoctors = User::query()
            ->where('role', 'doctor')
            ->whereHas(
                'doctorSchedules',
                fn ($q) => $q->where('day', $day)
            )
            ->with('poli')
            ->get()
            ->map(fn (User $doctor, $index) => [
                'doctor' => $doctor->name,
                'poli' => $doctor->poli?->name ?? '-',
                'room' => 'Ruang 0' . ($index + 1),
            ])
            ->all();

        return [
            'stats' => [
                'patients_today' => $patientsToday,
                'doctors_on_duty' => $doctorsOnDuty,
                'prescriptions_pending' => $prescriptionsPending,
                'new_patients' => $newPatients,
            ],
            'today' => $todayDoctors,
        ];
    }

    public function checkinBooking(
        string $code
    ): bool {
        return DB::transaction(function () use ($code) {
            $booking = Booking::query()
                ->lockForUpdate()
                ->where('code', $code)
                ->first();

            if (! $booking) {
                return false;
            }

            if (
                ! in_array(
                    $booking->status,
                    ['pending', 'approved'],
                    true
                )
            ) {
                return false;
            }

            $booking->queue_no =
                $this->nextQueueNumber(
                    $booking->poli_id,
                    $booking->date
                );

            $booking->status = 'checked_in';
            $booking->checked_in_at = now();
            $booking->save();

            return true;
        });
    }

    public function createPatient(
        array $data
    ): array {
        return DB::transaction(function () use ($data) {
            $patient = Patient::create([
                'rm' => $data['rm']
                    ?? $this->generateRm(),
                'nik' => $data['nik'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth' => $data['birth'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'blood_type' => $data['blood_type'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'emergency_name' =>
                    $data['emergency_name'] ?? null,
                'emergency_relation' =>
                    $data['emergency_relation'] ?? null,
                'emergency_phone' =>
                    $data['emergency_phone'] ?? null,
                'insurance_type' =>
                    $data['insurance_type'] ?? null,
                'insurance_number' =>
                    $data['insurance_number'] ?? null,
            ]);

            $booking = $this->createBooking([
                'patient_id' => $patient->id,
                'poli' => $data['poli'],
                'doctor' => $data['doctor'],
                'date' => $data['date'],
                'time' => $data['time'],
                'complaint' =>
                    $data['complaint'] ?? 'Pasien walk-in',
                'type' => 'walkin',
                'status' => 'checked_in',
            ]);

            $bookingModel = Booking::query()
                ->where('code', $booking['code'])
                ->firstOrFail();

            if (! $bookingModel->queue_no) {
                $bookingModel->update([
                    'queue_no' =>
                        $this->nextQueueNumber(
                            $bookingModel->poli_id,
                            $bookingModel->date
                        ),
                ]);

                $booking['queue_no'] =
                    $bookingModel->queue_no;
            }

            return [
                'patient' => $this->patientProfile(
                    (string) $patient->id
                ),
                'booking' => $booking,
            ];
        });
    }

    private function generateRm(): string
    {
        $last = Patient::query()
            ->where('rm', 'like', 'RM-%')
            ->orderByDesc('id')
            ->value('rm');

        $number = 0;

        if ($last && preg_match('/(\d+)$/', $last, $match)) {
            $number = (int) $match[1];
        }

        return 'RM-' . str_pad(
            (string) ($number + 1),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public function createBooking(
        array $data
    ): array {
        return DB::transaction(function () use ($data) {
            $patient = Patient::query()
                ->find($data['patient_id']);

            if (! $patient) {
                throw new \RuntimeException(
                    'Pasien tidak ditemukan.'
                );
            }

            $doctor = $this->resolveDoctor(
                (string) $data['doctor']
            );

            if (! $doctor) {
                throw new \RuntimeException(
                    'Dokter tidak ditemukan.'
                );
            }

            $poli = Poli::query()
                ->where('name', $data['poli'])
                ->first();

            if (! $poli) {
                throw new \RuntimeException(
                    'Poli tidak ditemukan.'
                );
            }

            $booking = Booking::create([
                'code' => $this->generateBookingCode(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'poli_id' => $poli->id,
                'date' => $data['date'],
                'time' => $data['time'],
                'complaint' =>
                    $data['complaint'] ?? null,
                'type' => $data['type'] ?? 'online',
                'status' => $data['status'] ?? 'pending',
                'queue_no' => null,
            ]);

            if (
                ($data['type'] ?? 'online') === 'walkin'
                || ($data['status'] ?? '') === 'checked_in'
            ) {
                $booking->queue_no =
                    $this->nextQueueNumber(
                        $poli->id,
                        $booking->date
                    );

                $booking->checked_in_at = now();
                $booking->save();
            }

            return [
                'code' => $booking->code,
                'patient_id' => (string) $booking->patient_id,
                'patient' => $patient->name,
                'rm' => $patient->rm,
                'doctor' => $doctor->name,
                'poli' => $poli->name,
                'date' => $this->formatDate(
                    $booking->date
                ),
                'time' => $booking->time,
                'queue_no' => $booking->queue_no,
                'queue' => $booking->queue_no,
                'status' => $this->statusLabel(
                    $booking->status
                ),
                'complaint' => $booking->complaint,
                'type' => $booking->type,
            ];
        });
    }

    public function updatePatient(
        string $id,
        array $data
    ): bool {
        $patient = Patient::query()->find($id);

        if (! $patient) {
            return false;
        }

        $patient->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
        ]);

        return true;
    }

    public function deletePatient(
        string $id
    ): bool {
        $patient = Patient::query()->find($id);

        if (! $patient) {
            return false;
        }

        return (bool) $patient->delete();
    }

    public function updateBooking(
        string $code,
        array $data
    ): bool {
        $booking = Booking::query()
            ->where('code', $code)
            ->first();

        if (! $booking) {
            return false;
        }

        $action = $data['action'] ?? null;

        if ($action === 'approve') {
            $booking->update([
                'status' => 'approved',
                'rejection_reason' => null,
            ]);

            return true;
        }

        if ($action === 'reject') {
            $booking->update([
                'status' => 'rejected',
                'rejection_reason' =>
                    $data['reason'] ?? null,
            ]);

            return true;
        }

        if ($action === 'cancel') {
            $booking->update([
                'status' => 'cancelled',
                'rejection_reason' =>
                    $data['reason'] ?? null,
            ]);

            return true;
        }

        if ($action === 'reschedule') {
            $booking->update([
                'date' => $data['date'],
                'time' => $data['time'],
                'status' => 'pending',
                'rejection_reason' => null,
                'queue_no' => null,
            ]);

            return true;
        }

        return false;
    }

    public function createSchedule(
        array $data
    ): bool {
        $doctor = $this->resolveDoctor(
            (string) $data['doctor']
        );

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
                'room' => $data['room'] ?? null,
                'quota' => $data['quota'],
            ]
        );

        return true;
    }

    public function weekSchedule(
        string $doctorId
    ): array {
        $doctor = $this->resolveDoctor($doctorId);

        if (! $doctor) {
            return [];
        }

        $poli = $doctor->poli?->name ?? '-';

        return DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->orderBy('day')
            ->get()
            ->map(function (DoctorSchedule $s) use ($poli) {
                $booked = Booking::query()
                    ->where('doctor_id', $s->doctor_id)
                    ->whereRaw(
                        'DAYOFWEEK(date) - 2 = ?',
                        [$s->day]
                    )
                    ->whereIn('status', [
                        'pending',
                        'approved',
                        'checked_in',
                    ])
                    ->count();

                return [
                    'day' => $s->day,
                    'start' => $s->start,
                    'end' => $s->end,
                    'poli' => $poli,
                    'quota' => $s->quota,
                    'booked' => $booked,
                ];
            })
            ->all();
    }

    public function leaveConflicts(
        string $doctorId,
        string $date
    ): array {
        $doctor = $this->resolveDoctor($doctorId);

        if (! $doctor) {
            return [];
        }

        return Booking::query()
            ->with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', $date)
            ->whereIn('status', [
                'pending',
                'approved',
                'checked_in',
            ])
            ->orderBy('time')
            ->get()
            ->map(fn (Booking $b) => [
                'patient' => $b->patient?->name,
                'time' => $b->time,
                'status' => $this->statusLabel(
                    $b->status
                ),
            ])
            ->all();
    }

    public function createStaff(
        array $data
    ): array {
        $poli = null;

        if (! empty($data['poli'])) {
            $poli = Poli::query()
                ->where('name', $data['poli'])
                ->first();
        }

        $user = User::create([
            'name' => $data['name'],
            'email' =>
                $data['email']
                ?? $data['username'] . '@medika.test',
            'username' => $data['username'],
            'password' => Hash::make(
                $data['password']
            ),
            'role' => $data['role'],
            'phone' => $data['phone'],
            'sip' => $data['sip'] ?? null,
            'poli_id' => $poli?->id,
        ]);

        return $this->staff(
            $data['role']
        )[
            count($this->staff($data['role'])) - 1
        ] ?? [
            'id' => (string) $user->id,
            'name' => $user->name,
        ];
    }

    public function updateStaff(
        string $id,
        array $data
    ): bool {
        $user = User::query()->find($id);

        if (! $user) {
            $doctorCode = strtoupper($id);

            if (preg_match('/^D-\d{3}$/', $doctorCode)) {
                $user = $this->resolveDoctor($doctorCode);
            }
        }

        if (! $user) {
            return false;
        }

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'status' => $data['status'] ?? 'Aktif',
        ]);

        return true;
    }

    public function deleteStaff(
        string $id
    ): bool {
        $user = User::query()->find($id);

        if (! $user && preg_match('/^D-\d{3}$/i', $id)) {
            $user = $this->resolveDoctor($id);
        }

        if (! $user) {
            return false;
        }

        return (bool) $user->delete();
    }
}