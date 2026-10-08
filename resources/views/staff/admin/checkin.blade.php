@extends('layouts.staff')
@section('title', 'Check-in Pasien')
@section('crumb', 'Check-in kedatangan')
@section('heading', 'Check-in Pasien')
@section('subheading', 'Konfirmasi kedatangan pasien untuk kunjungan hari ini.')
@section('content')
<div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
    <x-card class="h-fit p-5">
        <h2 class="font-semibold">Cari pasien</h2>
        <p class="text-sm text-slate-500">Gunakan NIK atau nomor tiket booking pasien.</p>
        <form method="GET" class="mt-4 space-y-3">
            <label class="label">NIK atau nomor tiket</label>
            <div class="relative"><x-icon name="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input name="q" value="{{ $q }}" class="input pl-10" placeholder="Contoh: MK-031026-003"></div>
            <button class="btn btn-primary w-full"><x-icon name="lucide:search" /> Cari</button>
        </form>
       
    </x-card>

    <x-card class="p-5">
        @if (! $q)
            <x-empty icon="lucide:ticket" title="Mulai dari tiket atau NIK pasien" text="Data pasien dan detail booking akan muncul di sini setelah pencarian." />
        @elseif (! $booking)
            <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><x-icon name="lucide:alert-circle" class="mt-0.5 text-lg" />
                <div><p class="font-semibold">Tiket tidak ditemukan</p><p>Periksa kembali nomor tiket atau gunakan NIK pasien untuk mencari ulang.</p></div></div>
            <p class="mt-5 font-semibold">Belum ada booking yang cocok</p>
            <ul class="mt-2 space-y-1.5 text-sm text-slate-600"><li>Pastikan nomor tiket ditulis dengan benar.</li><li>Gunakan NIK sebagai alternatif.</li><li>Pasien tanpa booking dapat didaftarkan langsung di menu Data pasien.</li></ul>
        @else
            @php $b = $booking; $checked = session('checked_in') || $b['state'] === 'checked_in'; @endphp
            @if (session('checked_in'))
                <div class="mb-4 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><x-icon name="lucide:check-circle-2" class="mt-0.5 text-lg" />
                    <div><p class="font-semibold">Check-in berhasil</p><p>Kedatangan {{ $b['patient'] }} dicatat. Arahkan pasien ke ruang {{ $b['poli'] }}.</p></div></div>
            @endif
            @if ($b['state'] === 'wrong_day')
                <div class="mb-4 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><x-icon name="lucide:calendar-x" class="mt-0.5 text-lg" />
                    <div><p class="font-semibold">Tiket bukan untuk hari ini</p><p>Booking berlaku pada {{ $b['date'] }}. Minta pasien datang sesuai jadwal.</p></div></div>
            @endif

            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Data pasien</h2>
                <x-badge :text="$checked ? 'Sudah check-in' : ($b['state'] === 'wrong_day' ? 'Bukan hari ini' : 'Siap check-in')" :tone="$checked ? 'green' : ($b['state'] === 'wrong_day' ? 'red' : 'amber')" />
            </div>
            <div class="mt-3 rounded-xl bg-slate-50 p-4">
                <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-full bg-white text-brand-600"><x-icon name="lucide:user" /></span>
                    <div><p class="font-semibold">{{ $b['patient'] }}</p><p class="text-xs text-slate-500">No. rekam medis: {{ $b['rm'] }}</p></div></div>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    @foreach (['NIK' => $b['nik'], 'Tanggal lahir' => $b['birth'], 'Jenis kelamin' => $b['gender'], 'Nomor telepon' => $b['phone']] as $k => $v)
                        <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <h3 class="mt-5 font-semibold">Detail booking</h3>
            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                @foreach (['Nomor tiket' => $b['code'], 'Tanggal kunjungan' => $b['date'], 'Poli' => $b['poli'], 'Dokter' => $b['doctor'], 'Jam kunjungan' => $b['time'], 'Nomor antrean' => $b['queue']] as $k => $v)
                    <div><dt class="text-xs text-slate-500">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>

            <form method="POST" action="{{ route('admin.checkin.store') }}" class="mt-6">@csrf
                <input type="hidden" name="code" value="{{ $b['code'] }}">
                <button class="btn btn-primary w-full" @disabled($checked || $b['state'] === 'wrong_day')><x-icon name="lucide:log-in" /> Check-in</button>
                @if ($b['state'] === 'wrong_day')<p class="hint text-center">Check-in hanya tersedia pada tanggal kunjungan.</p>@endif
            </form>
        @endif
    </x-card>
</div>
@endsection
