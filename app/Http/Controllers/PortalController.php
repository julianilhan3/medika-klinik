<?php

namespace App\Http\Controllers;

use App\Contracts\ClinicRepository;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class PortalController extends Controller
{
    public function __construct(private ClinicRepository $repo) {}

    public function showLogin()
    {
        return view('auth.patient-login');
    }

    public function showRegister()
    {
        return view('auth.patient-register');
    }

    public function login(Request $r)
    {
        $d = $r->validate([
            'nik' => 'required|digits:16',
            'password' => 'required',
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
        ]);

        $patient = Patient::query()
            ->where('nik', $d['nik'])
            ->first();

        if (! $patient || ! $patient->password || ! Hash::check($d['password'], $patient->password)) {
            return back()
                ->withInput($r->only('nik'))
                ->with('error', 'NIK atau password salah.');
        }

        $r->session()->regenerate();

        $r->session()->put('patient', [
            'id' => (string) $patient->id,
            'name' => $patient->name,
            'nik' => $patient->nik,
            'phone' => $patient->phone,
        ]);

        return redirect()->route('portal.home');
    }

    public function register(Request $r)
    {
        $d = $r->validate([
            'nik' => 'required|digits:16|unique:patients,nik',
            'name' => 'required|string|max:100',
            'birth' => 'required|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8',
            'terms' => 'accepted',
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'terms.accepted' => 'Anda harus menyetujui Syarat dan Ketentuan.',
        ]);

        $patient = Patient::create([
            'rm' => $this->generateRm(),
            'nik' => $d['nik'],
            'name' => $d['name'],
            'gender' => $d['gender'],
            'birth' => $d['birth'],
            'phone' => $d['phone'],
            'password' => Hash::make($d['password']),
        ]);

        return redirect()
            ->route('portal.login')
            ->with('status', 'Akun berhasil dibuat. Silakan masuk.');
    }

    public function logout(Request $r)
    {
        $r->session()->forget('patient');
        $r->session()->forget('booking');
        $r->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function home()
    {
        $id = session('patient.id');

        $allTickets = collect($this->repo->tickets($id));

        $active = $allTickets
            ->whereIn('status', ['Aktif', 'Menunggu Persetujuan'])
            ->first();

        $featured = collect($this->repo->doctors())
            ->where('status', 'Aktif')
            ->take(4)
            ->map(fn ($d) => [
                'id' => $d['id'],
                'name' => $d['name'],
                'poli' => $d['poli'],
            ])
            ->values()
            ->all();

        return view('portal.home', [
            'active' => $active,
            'live' => $active
                ? $this->repo->queueStatus($active['code'])
                : null,
            'rx' => collect($this->repo->patientPrescriptions($id))->first(),
            'visit' => collect($this->repo->patientVisits($id))->first(),
            'featured' => $featured,
        ]);
    }

    public function doctors(Request $request)
    {
        $date = $this->cleanDate($request->query('date'));
        $poli = $request->query('poli');
        $q = trim((string) $request->query('q', ''));

        $day = Carbon::parse($date)->dayOfWeekIso - 1;

        $doctors = collect($this->repo->doctors())
            ->where('status', 'Aktif')
            ->when(
                $poli,
                fn ($c) => $c->where('poli', $poli)
            )
            ->when(
                $q !== '',
                fn ($c) => $c->filter(
                    fn ($d) => str_contains(
                        mb_strtolower($d['name']),
                        mb_strtolower($q)
                    )
                )
            )
            ->map(function ($d) use ($day) {
                $s = collect(
                    $this->repo->weekSchedule($d['id'])
                )->firstWhere('day', $day);

                $start = data_get($s, 'start');
                $end = data_get($s, 'end');

                $left = $s
                    ? max(
                        0,
                        (int) data_get($s, 'quota', 0)
                        - (int) data_get($s, 'booked', 0)
                    )
                    : 0;

                return [
                    'id' => $d['id'],
                    'name' => $d['name'],
                    'poli' => $d['poli'],
                    'time' => $s && $start && $end
                        ? str_replace(':', '.', $start)
                            . ' - '
                            . str_replace(':', '.', $end)
                        : null,
                    'quota' => $left,
                    'available' => $left > 0,
                    'label' => ! $s
                        ? 'Tidak praktik'
                        : ($left > 0 ? 'Tersedia' : 'Penuh'),
                ];
            })
            ->values();

        $polis = collect($this->repo->poli())
            ->pluck('name')
            ->all();

        return view(
            'portal.doctors',
            compact('doctors', 'polis', 'date', 'poli', 'q')
        );
    }

    public function booking(Request $r)
    {
        $doctors = $this->repo->doctors();

        $pre = collect($doctors)
            ->firstWhere('id', $r->query('doctor'));

        $date = $r->filled('date')
            ? $this->cleanDate($r->query('date'))
            : '';

        return view('portal.booking', [
            'poli' => $this->repo->poli(),
            'doctors' => $doctors,
            'slots' => $this->repo->slots(
                $pre['id'] ?? 'D-003',
                $date ?: now()->toDateString()
            ),
            'pre' => $pre
                ? [
                    'poli' => $pre['poli'],
                    'doctor' => $pre['name'],
                    'date' => $date,
                ]
                : null,
        ]);
    }

    public function storeBooking(Request $r)
    {
        $d = $r->validate([
            'poli' => 'required',
            'doctor' => 'required',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
            'complaint' => 'required|string|max:500',
        ]);

        $patientId = session('patient.id');

        if (! $patientId) {
            return redirect()
                ->route('portal.login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($this->repo->hasActiveTicket($patientId, $d['poli'])) {
            return back()
                ->withInput()
                ->with('duplicate', true);
        }

        try {
            $booking = $this->repo->createBooking([
                'patient_id' => $patientId,
                'poli' => $d['poli'],
                'doctor' => $d['doctor'],
                'date' => $d['date'],
                'time' => $d['time'],
                'complaint' => $d['complaint'],
                'type' => 'online',
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('booking.done')
            ->with('booking', [
                'code' => $booking['code'],
                'patient' => $booking['patient'],
                'rm' => $booking['rm'],
                'poli' => $booking['poli'],
                'doctor' => $booking['doctor'],
                'date' => $booking['date'],
                'time' => $booking['time'],
                'queue' => $booking['queue_no'] ?? null,
                'queue_no' => $booking['queue_no'] ?? null,
                'status' => $booking['status'] ?? 'Menunggu Persetujuan',
                'complaint' => $booking['complaint'] ?? null,
            ]);
    }

    public function bookingDone()
    {
        abort_unless(session('booking'), 404);

        return view('portal.booking-done', [
            'b' => session('booking'),
        ]);
    }

    public function tickets()
    {
        $all = collect(
            $this->repo->tickets(session('patient.id'))
        );

        return view('portal.tickets', [
            'active' => $all->whereIn(
                'status',
                ['Aktif', 'Menunggu Persetujuan']
            ),
            'history' => $all->whereNotIn(
                'status',
                ['Aktif', 'Menunggu Persetujuan']
            ),
        ]);
    }

    public function ticket(string $code)
    {
        $t = $this->repo->ticket($code);

        abort_unless($t, 404);

        if ((string) ($t['patient_id'] ?? '') !== (string) session('patient.id')) {
            abort(404);
        }

        return view('portal.ticket', [
            't' => $t,
            'live' => $t['status'] === 'Aktif'
                ? $this->repo->queueStatus($code)
                : null,
        ]);
    }

    public function cancelTicket(string $code)
    {
        $ticket = $this->repo->ticket($code);

        abort_unless($ticket, 404);

        if ((string) ($ticket['patient_id'] ?? '') !== (string) session('patient.id')) {
            abort(403);
        }

        if (! in_array(
            $ticket['status'],
            ['Aktif', 'Menunggu Persetujuan'],
            true
        )) {
            return back()->with(
                'error',
                'Tiket tersebut tidak dapat dibatalkan.'
            );
        }

        $updated = $this->repo->updateBooking($code, [
            'action' => 'cancel',
            'reason' => 'Dibatalkan oleh pasien.',
        ]);

        if (! $updated) {
            return back()->with(
                'error',
                'Booking gagal dibatalkan.'
            );
        }

        return redirect()
            ->route('tickets.index')
            ->with(
                'status',
                "Booking {$code} dibatalkan."
            );
    }

    public function records(Request $r)
    {
        $id = session('patient.id');
        $sort = $r->query('sort') === 'asc'
            ? 'asc'
            : 'desc';

        $rows = collect();
        $error = false;

        try {
            $rows = collect(
                $this->repo->patientVisits($id, null)
            )
                ->map(fn ($v) => $this->normalizeVisit($v))
                ->sortBy(
                    'date_sort',
                    SORT_REGULAR,
                    $sort === 'desc'
                )
                ->values();
        } catch (\Throwable $e) {
            report($e);
            $error = true;
        }

        return view('portal.records', [
            'profile' => $this->repo->patientProfile($id),
            'rows' => $rows,
            'sort' => $sort,
            'error' => $error,
        ]);
    }

    public function record(string $visitId)
    {
        $id = session('patient.id');

        $v = collect(
            $this->repo->patientVisits($id, null)
        )
            ->map(fn ($v) => $this->normalizeVisit($v))
            ->firstWhere('id', $visitId);

        abort_if(! $v, 404);

        return view('portal.record', [
            'profile' => $this->repo->patientProfile($id),
            'v' => $v,
        ]);
    }

    public function prescriptions()
    {
        $id = session('patient.id');

        return view('portal.prescriptions', [
            'profile' => $this->repo->patientProfile($id),
            'list' => $this->repo->patientPrescriptions($id),
        ]);
    }

    public function prescription(string $code)
    {
        $rx = collect(
            $this->repo->patientPrescriptions(
                session('patient.id')
            )
        )->firstWhere('code', $code);

        abort_if(! $rx, 404);

        $rx += [
            'time' => '-',
            'poli' => '-',
            'note' => '',
        ];

        $rx['items'] = collect($rx['items'] ?? [])
            ->map(
                fn ($i) => $i + [
                    'form' => '-',
                    'dose' => '-',
                    'qty' => '-',
                    'rule' => '-',
                ]
            )
            ->all();

        return view(
            'portal.prescription',
            compact('rx')
        );
    }

    public function profile()
    {
        return view('portal.profile', [
            'p' => $this->repo->patientProfile(
                session('patient.id')
            ),
        ]);
    }

    public function updateProfile(Request $r)
    {
        $d = $r->validate([
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'required|string|max:255',
            'blood_type' => 'nullable|in:A,B,AB,O',
            'allergies' => 'nullable|string|max:255',
            'emergency_name' => 'required|string|max:100',
            'emergency_phone' => 'required|string|max:20',
            'emergency_relation' => 'required|string|max:50',
            'insurance_type' => 'required|in:Umum,BPJS Kesehatan,Asuransi Swasta',
            'insurance_number' => 'nullable|string|max:30',
        ]);

        $ok = $this->repo->updatePatient(
            session('patient.id'),
            $d
        );

        if (! $ok) {
            return back()->with(
                'error',
                'Profil gagal diperbarui.'
            );
        }

        $patient = Patient::find(
            session('patient.id')
        );

        if ($patient) {
            session()->put('patient', [
                'id' => (string) $patient->id,
                'name' => $patient->name,
                'nik' => $patient->nik,
                'phone' => $patient->phone,
            ]);
        }

        return back()->with(
            'status',
            'Profil berhasil diperbarui.'
        );
    }

    public function password()
    {
        return view('staff.account.password');
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'min:8',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
                'confirmed',
                'different:current_password',
            ],
        ], [
            'password.min' => 'Gunakan minimal 8 karakter, dengan huruf dan angka.',
            'password.regex' => 'Gunakan minimal 8 karakter, dengan huruf dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $patient = Patient::find(
            session('patient.id')
        );

        if (! $patient) {
            return redirect()
                ->route('portal.login')
                ->with('error', 'Sesi pasien tidak ditemukan.');
        }

        if (
            ! $patient->password ||
            ! Hash::check(
                $r->current_password,
                $patient->password
            )
        ) {
            return back()->with(
                'error',
                'Password lama salah.'
            );
        }

        $patient->password = Hash::make(
            $r->password
        );

        $patient->save();

        return redirect()
            ->route('portal.password')
            ->with(
                'password_changed',
                true
            );
    }

    private function cleanDate(?string $value): string
    {
        try {
            $d = Carbon::parse(
                $value ?: now()->toDateString()
            );
        } catch (\Throwable) {
            $d = now();
        }

        return $d->lt(now()->startOfDay())
            ? now()->toDateString()
            : $d->toDateString();
    }

    private function normalizeVisit(array $v): array
    {
        $date = null;

        foreach ([
            fn () => Carbon::createFromLocaleFormat(
                'j F Y',
                'id',
                (string) $v['date']
            ),
            fn () => Carbon::parse(
                (string) $v['date']
            ),
        ] as $try) {
            try {
                $date = $try();
                break;
            } catch (\Throwable) {
            }
        }

        return $v + [
            'id' => $v['id']
                ?? $v['code']
                ?? md5(
                    ($v['date'] ?? '')
                    . ($v['doctor'] ?? '')
                ),
            'time' => $v['time'] ?? '-',
        ] + [
            'date_label' => $date
                ? $date->translatedFormat('j F Y')
                : (string) $v['date'],
            'date_sort' => $date?->timestamp ?? 0,
            'height' => $v['height'] ?? '-',
            'weight' => $v['weight'] ?? '-',
            'bp' => $v['bp'] ?? '-',
            'complaint' => $v['complaint'] ?? '-',
            'findings' => $v['findings']
                ?? $v['note']
                ?? '-',
            'diagnoses' => $v['diagnoses']
                ?? [[
                    'name' => $v['diagnosis'] ?? '-',
                    'code' => $v['icd'] ?? '',
                ]],
            'meds' => $v['meds'] ?? [],
            'note' => $v['note'] ?? '',
        ];
    }

    private function generateRm(): string
    {
        $last = Patient::query()
            ->orderByDesc('id')
            ->value('rm');

        $number = 1;

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $number = ((int) $m[1]) + 1;
        }

        return 'RM-' . str_pad(
            (string) $number,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}