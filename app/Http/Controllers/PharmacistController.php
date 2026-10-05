<?php

namespace App\Http\Controllers;

use App\Contracts\ClinicRepository;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PharmacistController extends Controller
{
    public function __construct(private ClinicRepository $repo) {}

    private function withStock(array $rx): array
    {
        $meds = collect($this->repo->medicines())->keyBy('name');

        $rx['items'] = array_map(function ($i) use ($meds) {
            $medicine = $meds->get($i['name']);

            $stock = (int) ($medicine['stock'] ?? 0);
            $qty = (int) ($i['qty'] ?? 0);

            $i['stock'] = $stock;
            $i['unit'] = strtolower(
                $medicine['unit'] ?? 'unit'
            );

            $i['availability'] =
                $stock <= 0
                    ? 'Habis'
                    : ($stock < $qty
                        ? 'Kurang'
                        : 'Tersedia');

            return $i;
        }, $rx['items'] ?? []);

        return $rx;
    }

    private function rxOrFail(string $code): array
    {
        $rx = $this->repo->prescription($code);

        abort_unless($rx, 404);

        return $this->withStock($rx);
    }

    public function summary()
    {
        $meds = collect($this->repo->medicines());
        $rx = collect($this->repo->prescriptions());

        return view('staff.pharmacist.summary', [
            'counts' => [
                'Menunggu' => $rx->where('status', 'Menunggu')->count(),
                'Diproses' => $rx->where('status', 'Diproses')->count(),
                'Siap' => $rx->where('status', 'Siap')->count(),
                'Diserahkan' => $rx->where('status', 'Diserahkan')->count(),
            ],
            'low' => $meds
                ->filter(
                    fn ($m) =>
                    (int) $m['stock'] <=
                    (int) ($m['min'] ?? $m['min_stock'] ?? 0)
                )
                ->values(),
            'recent' => $rx->take(4),
        ]);
    }

    public function prescriptions(Request $r)
    {
        $status = $r->query('status');

        return view('staff.pharmacist.prescriptions', [
            'rows' => $this->repo->prescriptions($status),
            'status' => $status,
        ]);
    }

    public function showPrescription(string $code)
    {
        return view('staff.pharmacist.prescription-show', [
            'rx' => $this->rxOrFail($code),
        ]);
    }

    public function advancePrescription(Request $r, string $code)
    {
        $r->validate([
            'status' => 'required|in:Diproses,Siap',
        ]);

        $rx = $this->rxOrFail($code);

        $current = $rx['status'];
        $next = $r->status;

        $valid = match ($current) {
            'Menunggu' => $next === 'Diproses',
            'Diproses' => $next === 'Siap',
            default => false,
        };

        if (! $valid) {
            return back()->with(
                'error',
                "Status resep tidak dapat diubah dari {$current} menjadi {$next}."
            );
        }

        if (
            $next === 'Siap' &&
            collect($rx['items'])->contains(
                fn ($i) =>
                $i['availability'] !== 'Tersedia'
            )
        ) {
            return back()->with(
                'error',
                'Masih ada obat yang tidak tersedia. Lengkapi stok terlebih dahulu.'
            );
        }

        Prescription::query()
            ->where('code', $code)
            ->update([
                'status' => $next,
            ]);

        return back()->with(
            'status',
            "Resep {$code} diperbarui menjadi {$next}."
        );
    }

    public function markUnavailable(Request $r, string $code)
    {
        $r->validate([
            'item' => 'required|string',
            'note' => 'required|string|max:200',
        ], [
            'note.required' =>
                'Catatan untuk dokter wajib diisi.',
        ]);

        $rx = Prescription::query()
            ->where('code', $code)
            ->firstOrFail();

        $oldNote = trim((string) $rx->note);

        $newNote = trim(
            $oldNote .
            "\nObat {$r->item}: {$r->note}"
        );

        $rx->update([
            'note' => $newNote,
        ]);

        return redirect()
            ->route(
                'pharmacist.prescriptions.show',
                $code
            )
            ->with(
                'status',
                "{$r->item} ditandai tidak tersedia. Catatan disimpan untuk dokter."
            );
    }

    public function handover()
    {
        return view('staff.pharmacist.handover', [
            'rows' => $this->repo->prescriptions('Siap'),
        ]);
    }

    public function showHandover(string $code)
    {
        $rx = $this->rxOrFail($code);

        abort_unless(
            in_array(
                $rx['status'],
                ['Siap', 'Diserahkan'],
                true
            ),
            404
        );

        return view(
            'staff.pharmacist.handover-show',
            ['rx' => $rx]
        );
    }

    public function callPatient(string $code)
    {
        $rx = $this->rxOrFail($code);

        $prescription = Prescription::query()
            ->where('code', $code)
            ->firstOrFail();

        if (! $prescription->pharmacy_no) {
            $last = Prescription::query()
                ->whereDate('created_at', now()->toDateString())
                ->whereNotNull('pharmacy_no')
                ->count();

            $prescription->update([
                'pharmacy_no' =>
                    'F-' . str_pad(
                        $last + 1,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
            ]);
        }

        return back()->with(
            'status',
            "Pasien {$rx['patient']} ({$prescription->pharmacy_no}) dipanggil."
        );
    }

    public function confirmHandover(
        Request $r,
        string $code
    ) {
        $r->validate([
            'verified' => 'accepted',
        ], [
            'verified.accepted' =>
                'Konfirmasi identitas pasien terlebih dahulu.',
        ]);

        DB::transaction(function () use ($code) {
            $prescription = Prescription::query()
                ->with('items.medicine')
                ->where('code', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if ($prescription->status !== 'Siap') {
                throw new \RuntimeException(
                    'Resep belum siap diserahkan.'
                );
            }

            foreach ($prescription->items as $item) {
                $medicine = Medicine::query()
                    ->lockForUpdate()
                    ->findOrFail($item->medicine_id);

                $qty = (int) $item->qty;

                if ($medicine->stock < $qty) {
                    throw new \RuntimeException(
                        "Stok {$medicine->name} tidak mencukupi."
                    );
                }

                $medicine->decrement('stock', $qty);

                StockMovement::create([
                    'medicine_id' => $medicine->id,
                    'qty' => $qty,
                    'expiry' => $medicine->expiry,
                    'batch' => null,
                    'type' => 'out',
                    'note' => "Penyerahan resep {$prescription->code}",
                ]);
            }

            $prescription->update([
                'status' => 'Diserahkan',
                'handed_by' => Auth::id(),
                'handed_at' => now(),
            ]);
        });

        return redirect()
            ->route('pharmacist.handover')
            ->with(
                'status',
                "Obat untuk resep {$code} telah diserahkan."
            );
    }

    public function history()
    {
        return view('staff.pharmacist.history', [
            'rows' => $this->repo->prescriptions('Diserahkan'),
        ]);
    }

    public function medicines(Request $r)
    {
        return view('staff.pharmacist.medicines', [
            'rows' => $this->repo->medicines(
                $r->query('q')
            ),
            'q' => $r->query('q'),
        ]);
    }

    public function storeMedicine(Request $r)
    {
        $r->validate([
            'code' => 'required',
            'name' => 'required',
            'category' => 'required',
            'unit' => 'required',
            'price' => 'required|integer|min:0',
            'min' => 'required|integer|min:0',
        ]);

        $data = [
            'code' => $r->code,
            'name' => $r->name,
            'category' => $r->category,
            'unit' => $r->unit,
            'price' => $r->price,
            'min_stock' => $r->min,
            'stock' => $r->input('stock', 0),
            'expiry' => $r->input('expiry'),
            'class' => $r->input('class'),
            'form' => $r->input('form'),
        ];

        if ($r->filled('id')) {
            Medicine::query()
                ->whereKey($r->id)
                ->update($data);
        } else {
            Medicine::create($data);
        }

        return back()->with(
            'status',
            'Data obat disimpan.'
        );
    }

    public function destroyMedicine(string $id)
    {
        $medicine = Medicine::query()->findOrFail($id);
        $medicine->delete();

        return back()->with(
            'status',
            'Obat dihapus dari daftar.'
        );
    }

    public function stock()
    {
        return view('staff.pharmacist.stock', [
            'rows' => $this->repo->medicines(),
        ]);
    }

    public function addStock(Request $r)
    {
        $r->validate([
            'medicine' => 'required',
            'qty' => 'required|integer|min:1',
            'expiry' => 'required|date',
            'batch' => 'nullable',
        ]);

        DB::transaction(function () use ($r) {
            $medicine = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($r->medicine);

            $medicine->increment(
                'stock',
                (int) $r->qty
            );

            $medicine->update([
                'expiry' => $r->expiry,
            ]);

            StockMovement::create([
                'medicine_id' => $medicine->id,
                'qty' => $r->qty,
                'expiry' => $r->expiry,
                'batch' => $r->batch,
                'type' => 'in',
                'note' => 'Stok masuk',
            ]);
        });

        return back()->with(
            'status',
            'Stok masuk dicatat.'
        );
    }
}