@extends('layouts.staff')
@section('title', 'Rekam Medis Pasien')
@section('crumb', 'Antrean pasien / Rekam medis')
@section('heading', 'Rekam Medis Pasien')
@section('subheading', 'Tinjau identitas, alergi, dan riwayat sebelum pemeriksaan.')
@section('actions')<a href="{{ route('doctor.queue') }}" class="btn btn-outline"><x-icon name="lucide:arrow-left" /> Kembali ke antrean</a>@endsection
@section('content')

@if (!empty($p['allergies']))
<div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
    <span class="grid size-9 place-items-center rounded-lg bg-red-600 text-white"><x-icon name="lucide:shield-alert" /></span>
    <div class="min-w-0 flex-1">
        <p class="font-semibold">Alergi obat: {{ is_array($p['allergies']) ? implode(', ', $p['allergies']) : $p['allergies'] }}</p>
        <p>Reaksi tercatat: {{ strtolower($p['allergy_reaction'] ?? 'tidak diketahui') }}. Perhatikan alergi ini sebelum meresepkan obat.</p>
    </div>
    <span class="text-xs font-bold tracking-wide">ALERGI OBAT</span>
</div>
@endif

<x-card class="p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid size-12 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">{{ collect(explode(' ', $p['patient'] ?? 'A'))->take(2)->map(fn ($w) => $w[0] ?? '')->implode('') }}</span>
            <div>
                <p class="text-lg font-bold">{{ $p['patient'] ?? 'Nama Tidak Diketahui' }}</p>
                <p class="text-sm text-slate-500">{{ $p['rm'] ?? '-' }} - Pasien lama</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-badge text="Diperiksa" />
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">Antrean {{ $p['no'] ?? '-' }}</span>
        </div>
    </div>
    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
        @foreach (['Umur' => isset($p['age']) ? $p['age'].' tahun' : '-', 'Jenis kelamin' => $p['gender'] ?? '-', 'NIK' => $p['nik'] ?? '-', 'Tanggal lahir' => !empty($p['dob']) ? \Illuminate\Support\Carbon::parse($p['dob'])->translatedFormat('j F Y') : '-'] as $k => $v_demografi)
            <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-semibold">{{ $v_demografi }}</dd></div>
        @endforeach
    </dl>
</x-card>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
    <x-card class="p-5 sm:p-6">
        <div class="flex items-center justify-between"><div><h2 class="font-semibold">Riwayat kunjungan</h2><p class="text-sm text-slate-500">Terbaru ditampilkan lebih dulu. Pilih kunjungan untuk melihat catatan.</p></div><span class="text-xs text-slate-500">{{ count($visits ?? []) }} kunjungan</span></div>
        <ol class="mt-5 space-y-4 border-l-2 border-slate-200 pl-5">
        @foreach ($visits ?? [] as $v)
            <li x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="relative">
                <span class="absolute -left-[1.65rem] top-5 size-3 rounded-full border-2 border-white {{ $loop->first ? 'bg-brand-600' : 'bg-slate-300' }}"></span>
                <div class="rounded-xl border p-4 {{ $loop->first ? 'border-brand-600/40' : 'border-slate-200' }}">
                    <button @click="open = !open" class="flex w-full items-start justify-between gap-3 text-left">
                        <div>
                            <p class="font-semibold">{{ $v['date'] ?? 'Tanggal Tidak Diketahui' }}</p>
                            <p class="text-xs text-slate-500">{{ $v['doctor'] ?? 'Dokter Tidak Diketahui' }} - Poli Umum {{ isset($v['time']) ? '- '.$v['time'].' WIB' : '' }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <x-badge :text="$v['status'] ?? 'Selesai'" />
                            <span class="flex items-center gap-1 text-xs font-medium text-brand-600">
                                <span x-text="open ? 'Tutup detail' : 'Lihat detail'"></span>
                                <x-icon name="lucide:chevron-down" class="text-base transition" ::class="open && 'rotate-180'" />
                            </span>
                        </div>
                    </button>
                    <div x-show="open" x-cloak class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                        <p class="font-semibold">Catatan pemeriksaan</p>
                        <dl class="grid grid-cols-3 gap-2">
                            @foreach (['Tekanan darah' => isset($v['bp']) ? $v['bp'].' mmHg' : '-', 'Nadi' => isset($v['pulse']) ? $v['pulse'].' x/menit' : '-', 'Suhu' => isset($v['temp']) ? $v['temp'].' C' : '-'] as $k => $val)
                                <div class="rounded-lg border border-slate-200 p-2.5"><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-semibold">{{ $val }}</dd></div>
                            @endforeach
                        </dl>
                        @foreach ([
                            'Keluhan dan anamnesis' => trim(($v['complaint'] ?? '') . ' ' . ($v['anamnesis'] ?? '')) ?: '-', 
                            'Diagnosis' => trim(($v['diagnosis'] ?? '') . ' ' . (!empty($v['icd']) ? '('.$v['icd'].')' : '')) ?: '-', 
                            'Terapi tercatat' => $v['therapy'] ?? '-', 
                            'Edukasi dan rencana kontrol' => $v['education'] ?? '-'
                        ] as $k => $val)
                            <div><p class="text-xs text-slate-500">{{ $k }}</p><p>{{ $val }}</p></div>
                        @endforeach
                    </div>
                </div>
            </li>
        @endforeach
        </ol>
        <p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:lock" class="text-base" /> Catatan kunjungan sebelumnya hanya dapat dilihat.</p>
    </x-card>

    <div class="space-y-6">
        <x-card class="p-5 text-sm">
            <h2 class="flex items-center gap-2 font-semibold"><x-icon name="lucide:stethoscope" class="text-brand-600" /> Kunjungan hari ini</h2>
            <dl class="mt-3 space-y-3">
                @foreach (['Tanggal kunjungan' => now()->translatedFormat('j F Y').' - '.($p['registered'] ?? '').' WIB', 'Keluhan awal' => $p['complaint'] ?? '-', 'Dokter pemeriksa' => session('staff.name') ?? '-', 'Lokasi layanan' => 'Poli Umum - Ruang 01'] as $k => $v_today)
                    <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-semibold">{{ $v_today }}</dd></div>
                @endforeach
            </dl>
        </x-card>
        <x-card class="p-5">
            <h2 class="font-semibold">Lanjutkan pemeriksaan</h2>
            <p class="mt-1 text-sm text-slate-500">Pastikan identitas dan alergi pasien sudah ditinjau.</p>
            <a href="{{ route('doctor.exam', $p['no'] ?? '1') }}" class="btn btn-primary mt-4 w-full"><x-icon name="lucide:arrow-right" /> Lanjut ke Pemeriksaan</a>
            <p class="mt-2 text-xs text-slate-500">Membuka formulir pemeriksaan, bukan menyelesaikan kunjungan.</p>
        </x-card>
    </div>
</div>
@endsection