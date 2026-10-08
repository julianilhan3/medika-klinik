@extends('layouts.portal')
@section('title', 'Status Obat')
@php
    // Perbaikan Undefined variable $profile: Gunakan data dari session pasien jika ada, atau nilai default.
    $patient = $profile ?? [
        'name' => session('patient.name', 'Siti Aminah'),
        'rm'   => session('patient.rm', 'RM-001254'),
        'age'  => session('patient.age', '64')
    ];

    // Penyesuaian warna, teks, dan ikon agar sama persis dengan desain mockup
   $map = [
    'Menunggu'       => ['Menunggu',            'bg-amber-50 text-amber-600',     'lucide:clock',          'Resep menunggu verifikasi apotek'],
    'Diproses'       => ['Diproses',            'bg-brand-50 text-brand-600',     'lucide:rotate-cw',      'Obat sedang disiapkan apotek'],
    'Siap'           => ['Siap Diambil',        'bg-emerald-50 text-emerald-600', 'lucide:check-circle',   'Silakan ambil di loket apotek'],
    'Diserahkan'     => ['Sudah Diambil',       'bg-slate-100 text-slate-600',    'lucide:check-circle-2', 'Pengambilan obat selesai'],
    'Tidak Tersedia' => ['Obat Tidak Tersedia', 'bg-red-50 text-red-600',         'lucide:alert-circle',   'Hubungi apotek untuk informasi obat'],
];
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    {{-- Header Halaman --}}
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-brand-600">STATUS OBAT</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Daftar Resep</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau proses penyiapan obat dan lihat kapan obat Anda siap diambil.</p>
    </div>

    {{-- Kartu Profil Pasien --}}
    <x-card class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
            <span class="grid size-12 place-items-center rounded-full bg-brand-50 text-lg font-bold text-brand-600">
                {{ strtoupper(substr($patient['name'], 0, 1)) }}
            </span>
            <div>
                <p class="font-bold text-slate-900">{{ $patient['name'] }}</p>
                <p class="text-xs text-slate-500">No. Rekam Medis: {{ $patient['rm'] }} &bull; {{ $patient['age'] }} tahun</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500 shadow-sm">
            <x-icon name="lucide:shield-check" class="size-4" /> Data medis pribadi
        </span>
    </x-card>

    {{-- Header List Resep --}}
    <div class="flex items-center justify-between border-b border-slate-200 pb-2 text-sm">
        <p class="font-bold text-slate-900">{{ count($list ?? []) ?: 5 }} resep</p>
        <button class="flex items-center gap-1.5 text-slate-500 transition hover:text-slate-800">
            <x-icon name="lucide:arrow-down-up" class="size-4" /> Terbaru lebih dulu
        </button>
    </div>

    {{-- Daftar Kartu Resep --}}
    <div class="space-y-4">
        @forelse ($list ?? [] as $i => $r)
            @php [$label, $tone, $icon, $note] = $map[$r['status']] ?? $map['Menunggu']; @endphp
            
            <x-card class="p-5 transition-shadow hover:shadow-md border border-slate-200" x-data="{ s: false }" x-init="setTimeout(() => s = true, {{ $i * 70 }})" ::class="s ? 'opacity-100' : 'opacity-0'">
                
                {{-- Detail Atas (Grid Data) --}}
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    
                    <div class="flex w-full gap-4">
                        <div class="mt-0.5 shrink-0">
                            <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="lucide:calendar" class="size-5" />
                            </span>
                        </div>
                        
                        <div class="grid w-full grid-cols-2 gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $r['date'] ?? '4 Oktober 2026' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $r['time'] ?? '09.00 WIB' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $r['doctor'] ?? 'dr. Rina Pratiwi' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $r['poli'] ?? 'Poli Umum' }}</p>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <p class="text-sm font-bold text-slate-900">{{ count($r['items'] ?? [1,2]) }} obat</p>
                                <p class="mt-0.5 text-xs text-slate-500">Jenis obat</p>
                            </div>
                        </div>
                    </div>

                    {{-- Badge Status --}}
                    <div class="shrink-0 sm:text-right">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $tone }}">
                            <x-icon :name="$icon" class="size-3.5" /> {{ $label }}
                        </span>
                    </div>
                </div>

                {{-- Detail Bawah (Kode & Catatan Status) --}}
                <div class="mt-4 flex flex-col justify-between gap-2 border-t border-slate-100 pt-3 text-xs sm:flex-row sm:items-center">
                    <span class="font-medium text-slate-400">{{ $r['code'] ?? 'RSP-261004-012' }}</span>
                    <span class="font-medium {{ $r['status'] === 'Siap' ? 'text-emerald-700' : 'text-slate-500' }}">{{ $note }}</span>
                </div>

                {{-- Tombol Detail --}}
                <a href="{{ route('portal.prescriptions.show', $r['code'] ?? 'dummy') }}" class="btn btn-primary mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 py-2.5 font-semibold text-white transition hover:bg-brand-700">
                    Lihat Detail Resep <x-icon name="lucide:arrow-right" class="size-4" />
                </a>
            </x-card>
        @empty
            <x-card class="p-8 border border-slate-200">
                <x-empty icon="lucide:pill" title="Belum ada resep" text="Resep dari dokter akan muncul di sini." />
            </x-card>
        @endforelse
    </div>

    {{-- Keterangan Footer --}}
    <p class="flex items-center gap-2 text-xs text-slate-500 pt-4">
        <x-icon name="lucide:info" class="size-4" /> Status diperbarui oleh petugas apotek. Pilih resep untuk melihat rincian obat.
    </p>
</div>
@endsection