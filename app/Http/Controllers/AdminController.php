<?php

namespace App\Http\Controllers;

use App\Contracts\ClinicRepository;
use Illuminate\Http\Request;
use App\Support\DoctorPhoto;


class AdminController extends Controller
{
    public function __construct(private ClinicRepository $repo) {}

    public function dashboard()
    {
        return view('staff.admin.dashboard', ['data' => $this->repo->dashboard(), 'queue' => array_slice($this->repo->queue(), 0, 5)]);
    }

    public function queue(Request $r)
    {
        return view('staff.admin.queue', [
            'stats' => $this->repo->queueStats(),
            'rows' => $this->repo->queue($r->only('poli', 'status', 'q')),
            'poli' => $this->repo->poli(),
        ]);
    }

    public function checkin(Request $r)
    {
        $q = trim((string) $r->query('q'));
        return view('staff.admin.checkin', ['q' => $q, 'booking' => $q ? $this->repo->findBooking($q) : null]);
    }

    public function doCheckin(Request $r)
{
    $r->validate([
        'code' => 'required',
    ]);

    $ok = $this->repo->checkinBooking($r->code);

    if (! $ok) {
        return back()->with(
            'error',
            'Booking tidak dapat melakukan check-in.'
        );
    }

    return redirect()
        ->route('admin.checkin', [
            'q' => $r->code,
        ])
        ->with(
            'checked_in',
            true
        );
}

    // ---- Pasien
    public function patients(Request $r)
    {
        return view('staff.admin.patients', ['rows' => $this->repo->patients($r->query('q')), 'q' => $r->query('q')]);
    }

    public function patientSlots(Request $r)
{
    $data = $r->validate([
        'doctor' => 'required|string',
        'date' => 'required|date',
    ]);

    return response()->json(
        $this->repo->slots($data['doctor'], $data['date'])
    );
}
    public function createPatient()
    {
        return view('staff.admin.patient-create', [
            'poli' => $this->repo->poli(), 'doctors' => $this->repo->doctors(), 'slots' => $this->repo->slots('D-001', now()->toDateString()),
        ]);
    }

    public function lookupNik(Request $r)
    {
        // BACKEND: endpoint JSON dipakai tombol "Cari pasien" di form pendaftaran langsung.
        return response()->json($this->repo->patientByNik((string) $r->query('nik')));
    }

   public function storePatient(Request $r)
{   
    
    $d = $r->validate([
        'nik' => 'required|digits:16',
        'name' => 'required|max:100',
        'birth' => 'required|date',
        'gender' => 'required',
        'phone' => 'required',
        'address' => 'required',
        'poli' => 'required',
        'doctor' => 'required',
        'date' => 'required|date',
        'time' => 'required',
    ], [
        'nik.digits' => 'NIK harus 16 angka.',
    ]);

    try {
        $result = $this->repo->createPatient($d);

        return redirect()
            ->route('admin.patients.create')
            ->with('ticket', [
                'name' => $result['patient']->name,
                'poli' => $result['booking']['poli'],
                'doctor' => $result['booking']['doctor'],
                'date' => $result['booking']['date'],
                'time' => $result['booking']['time'],
                'queue' => $result['booking']['queue'],
                'code' => $result['booking']['code'],
            ]);
    } catch (\Throwable $e) {
        report($e);

        return back()
            ->withInput()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

  public function updatePatient(Request $r, string $id)
{
    $d = $r->validate([
        'name' => 'required',
        'phone' => 'required',
        'address' => 'required',
    ]);

    $ok = $this->repo->updatePatient($id, $d);

    if (! $ok) {
        return back()->with(
            'error',
            'Data pasien gagal diperbarui.'
        );
    }

    return back()->with(
        'status',
        'Data pasien diperbarui.'
    );
}

    public function destroyPatient(string $id)
{
    $ok = $this->repo->deletePatient($id);

    if (! $ok) {
        return back()->with(
            'error',
            'Data pasien gagal dihapus.'
        );
    }

    return back()->with(
        'status',
        'Data pasien dihapus.'
    );
}

    // ---- Reservasi (kelola booking)
    public function bookings(Request $r)
    {
        $all = collect($this->repo->bookings());
        return view('staff.admin.bookings', [
            'rows' => $this->repo->bookings($r->only('status', 'poli', 'q')),
            'counts' => ['total' => $all->count(), 'pending' => $all->where('status', 'Menunggu Persetujuan')->count(), 'approved' => $all->where('status', 'Disetujui')->count(), 'cancelled' => $all->where('status', 'Dibatalkan')->count()],
            'poli' => $this->repo->poli(),
            'slots' => $this->repo->slots('D-001', now()->toDateString()),
        ]);
    }

   public function updateBooking(Request $r, string $code)
{
    $d = $r->validate([
        'action' => 'required|in:approve,reject,cancel,reschedule',
        'reason' => 'required_if:action,reject,cancel|nullable|string|max:255',
        'date' => 'required_if:action,reschedule|nullable|date|after_or_equal:today',
        'time' => 'required_if:action,reschedule|nullable|string',
    ], [
        'reason.required_if' => 'Alasan wajib diisi.',
        'date.required_if' => 'Pilih tanggal baru.',
        'time.required_if' => 'Pilih jam baru.',
    ]);

    $ok = $this->repo->updateBooking(
        $code,
        $d
    );

    if (! $ok) {
        return back()->with(
            'error',
            'Reservasi gagal diperbarui.'
        );
    }

    $msg = [
        'approve' => 'disetujui',
        'reject' => 'ditolak',
        'cancel' => 'dibatalkan',
        'reschedule' => 'dijadwalkan ulang',
    ][$d['action']];

    return back()->with(
        'status',
        "Reservasi {$code} {$msg}."
    );
}
  public function schedule(Request $r)
{
    $doctors = $this->repo->doctors();

    abort_if(empty($doctors), 404);

    $doctorId = $r->query('doctor');

    $doctor = collect($doctors)->firstWhere('id', $doctorId)
        ?? $doctors[0];

    $week = (int) $r->query('week', 0);

    $sessions = collect(
        $this->repo->weekSchedule($doctor['id'])
    );

    $stats = [
        'sessions' => $sessions->count(),
        'quota' => (int) $sessions->sum('quota'),
        'booked' => (int) $sessions->sum('booked'),
    ];

    $weekStart = now()
        ->startOfWeek()
        ->addWeeks($week);

    return view('staff.admin.schedule', [
        'doctors' => $doctors,
        'doctor' => $doctor,
        'sessions' => $sessions->all(),
        'stats' => $stats,
        'weekStart' => $weekStart,
        'week' => $week,
        'conflict' => [],
    ]);
}
public function storeSchedule(Request $r)
{
    $d = $r->validate([
        'doctor' => 'required',
        'day' => 'required|integer|between:0,6',
        'start' => 'required',
        'end' => 'required|after:start',
        'quota' => 'required|integer|min:1|max:100',
    ]);

    $ok = $this->repo->createSchedule($d);

    if (! $ok) {
        return back()->with(
            'error',
            'Jadwal gagal disimpan atau bentrok dengan jadwal lain.'
        );
    }

    return back()->with(
        'status',
        'Jadwal praktik disimpan.'
    );
}

public function blockLeave(Request $r)
{
    
        $d = $r->validate(['doctor' => 'required', 'date' => 'required|date|after_or_equal:today', 'reason' => 'required|string|max:255', 'scope' => 'required|in:full,session', 'confirm' => 'nullable|boolean']);
        $conflicts = $this->repo->leaveConflicts($d['doctor'], $d['date']);

        if ($conflicts && ! $r->boolean('confirm')) {
            $doc = collect($this->repo->doctors())->firstWhere('id', $d['doctor']);
            return back()->withInput()->with('leave_conflict', [
                'rows' => $conflicts, 'doctor' => $doc['name'], 'poli' => $doc['poli'], 'date' => \Illuminate\Support\Carbon::parse($d['date'])->translatedFormat('l, j F Y'),
                'reason' => $d['reason'], 'scope' => $d['scope'], 'raw' => $d,
            ]);
        }

        // BACKEND: simpan cuti; jika $conflicts ada dan confirm = 1, batalkan reservasi + notifikasi pasien dalam satu transaksi.
        $n = count($conflicts);
        return redirect()->route('admin.schedule', ['doctor' => $d['doctor']])->with('status', $n ? "Cuti diblokir dan {$n} reservasi dibatalkan. Pasien diberi tahu." : 'Cuti diblokir.');
    }

    public function reports()
    {
        return view('staff.admin.reports', ['r' => $this->repo->report()]);
    }

    // ---- Kelola staf (dokter & apoteker)
    public function staff(Request $r)
    {
        $tab = $r->query('tab') === 'pharmacist' ? 'pharmacist' : 'doctor';
        return view('staff.admin.staff', ['tab' => $tab, 'rows' => $this->repo->staff($tab), 'poli' => $this->repo->poli()]);
    }

  public function storeStaff(Request $r)
{
    $r->validate([
        'role' => 'required|in:doctor,pharmacist',
        'name' => 'required',
        'username' => 'required|alpha_dash|unique:users,username',
        'phone' => 'required',
        'password' => 'required|min:8',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'poli' => 'nullable|string',
    ]);

    try {
        $this->repo->createStaff(
    $r->only([
        'role',
        'name',
        'username',
        'phone',
        'password',
        'email',
        'sip',
        'poli_id',
        'poli',
    ])
);

        $this->savePhoto($r);

        return redirect()
            ->route('admin.staff.index', [
                'tab' => $r->role,
            ])
            ->with(
                'status',
                'Akun staf dibuat.'
            );
    } catch (\Throwable $e) {
        report($e);

        return back()
            ->withInput()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

public function updateStaff(Request $r, string $id)
{
    $r->validate([
        'name' => 'required',
        'phone' => 'required',
        'status' => 'required',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $tab = $r->input('role') === 'pharmacist'
        ? 'pharmacist'
        : 'doctor';

    $oldName = data_get(
        collect($this->repo->staff($tab))
            ->firstWhere('id', $id),
        'name'
    );

    $ok = $this->repo->updateStaff(
    $id,
    $r->only([
        'name',
        'phone',
        'status',
        'email',
        'sip',
        'poli_id',
        'poli',
    ])
);

    if (! $ok) {
        return back()->with(
            'error',
            'Data staf gagal diperbarui.'
        );
    }

    $this->savePhoto(
        $r,
        $oldName
    );

    return back()->with(
        'status',
        'Data staf diperbarui.'
    );
}
public function destroyStaff(string $id)
{
    $ok = $this->repo->deleteStaff($id);

    if (! $ok) {
        return back()->with(
            'error',
            'Data staf gagal dihapus.'
        );
    }

    return back()->with(
        'status',
        'Data staf dihapus.'
    );
}

/** Foto profil dokter: disimpan berdasarkan nama, jadi otomatis tampil di semua halaman yang menampilkan dokter tersebut. */
private function savePhoto(Request $r, ?string $oldName = null): void
{
    if ($r->input('role') !== 'doctor') return;

    $name = $r->input('name');
    if ($oldName) DoctorPhoto::rename($oldName, $name);
    if ($r->boolean('remove_photo')) DoctorPhoto::forget($name);
    if ($r->hasFile('photo')) DoctorPhoto::store($name, $r->file('photo'));
}
}
