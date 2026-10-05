@extends('layouts.portal')
@section('title', 'Beranda')
@section('content')
<div class="space-y-8">

    {{-- Hero --}}
    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="grid items-center gap-6 p-6 sm:p-8 md:grid-cols-2">
            <div>
                <p class="text-sm font-medium text-slate-500">Selamat pagi, {{ session('patient.name') ?? 'Bapak Budi' }}</p>
                <h1 class="mt-2 text-3xl font-bold leading-tight text-slate-900">Selamat Datang di Medika Klinik</h1>
                <p class="mt-3 text-sm text-slate-500">Daftar berobat mudah tanpa antre lama</p>
                <a href="{{ route('booking') }}" class="btn btn-primary mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-6 py-2.5 font-medium text-white transition-colors hover:bg-brand-700">
                    <x-icon name="lucide:calendar" class="size-5" /> Booking Online
                </a>
                <p class="mt-3 text-xs text-slate-400">Pilih dokter dan waktu kunjungan Anda</p>
            </div>
            <div class="flex h-full w-full items-center justify-center rounded-xl bg-slate-50/50 p-4">
                <img src="{{ asset('images/Ilustrasi dokter ramah.png') }}" alt="Ilustrasi Dokter" class="mx-auto max-h-56 w-full object-contain mix-blend-multiply">
            </div>
        </div>
    </section>

    {{-- Menu cepat --}}
    <section>
        <h2 class="mb-4 font-bold text-slate-900">Apa yang Anda perlukan?</h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
           @foreach ([
    ['portal.doctors',       'lucide:calendar-days',  'Jadwal Dokter', 'Cari dokter dan jam praktik'],
    ['tickets.index',        'lucide:ticket',         'Tiket Antrean', 'Lihat nomor antrean Anda'],
    ['portal.records',       'lucide:clipboard-plus', 'Riwayat Medis', 'Catatan kunjungan Anda'],
    ['portal.prescriptions', 'lucide:pill',           'Status Obat',   'Pantau kesiapan obat Anda'],
] as [$route, $icon, $label, $desc])
                <a href="{{ route($route) }}" class="group flex flex-col items-center rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200/70 transition hover:ring-brand-600/40">
                    <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon :name="$icon" class="size-6" /></span>
                    <p class="mt-4 text-sm font-bold text-slate-900">{{ $label }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $desc }}</p>
                    <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand-600">Lihat detail <x-icon name="lucide:arrow-right" class="size-3.5" /></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Kunjungan berikutnya + bantuan --}}
    <section class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <x-card class="flex flex-col p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h2 class="font-bold text-slate-900">Kunjungan berikutnya</h2>
                @if ($active) 
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-800">
                        <x-icon name="lucide:clock" class="size-3.5" /> <span class="px-2 py-1 mt-1">Menunggu</span>
                    </span>
                @endif
            </div>

            @if ($active)
                @php $d = \Carbon\Carbon::parse($active['date']); @endphp
                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 text-sm text-slate-600">
                        <div class="grid size-16 place-items-center rounded-xl bg-brand-50 text-center text-brand-600">
                            <div><p class="text-[10px] font-bold uppercase tracking-wider">{{ $d->translatedFormat('M') }}</p><p class="text-2xl font-bold leading-none">{{ $d->format('d') }}</p></div>
                        </div>
                        <div>
                            <p class="text-base font-bold text-slate-900">{{ $active['doctor'] }}</p>
                            <p class="mt-0.5">{{ $active['poli'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $d->translatedFormat('l, j F Y') }} &middot; {{ $active['time'] }}</p>
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs text-slate-500">No. antrean</p>
                        <p class="mt-0.5 text-2xl font-bold text-slate-900">{{ $active['queue'] }}</p>
                    </div>
                </div>
                
                {{-- Bagian ini tersembunyi jika layout tidak membutuhkan antrean live (dibiarkan dari source awal untuk fungsionalitas) --}}
                <div class="mt-4 hidden">@include('partials.queue-live', ['live' => $live])</div>
                
                <div class="mt-6 flex items-center justify-between pt-4 text-xs">
                    <p class="text-slate-500">Datang 15 menit sebelum jadwal Anda.</p>
                    <a href="{{ route('tickets.show', $active['code']) }}" class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:underline">Lihat tiket antrean <x-icon name="lucide:arrow-right" class="size-3.5" /></a>
                </div>
            @else
                <x-empty icon="lucide:calendar-plus" title="Belum ada booking aktif" text="Cari dokter lalu pilih jam kunjungan yang sesuai.">
                    <a href="{{ route('portal.doctors') }}" class="btn btn-primary mt-4">Cari dokter</a>
                </x-empty>
            @endif

            @if ($rx && $rx['status'] === 'Siap')
                <p class="mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                    <x-icon name="lucide:package-check" class="size-5" /> Obat Anda siap diambil. Tunjukkan kode {{ $rx['code'] }} di apotek.
                </p>
            @endif
        </x-card>

        <x-card class="p-6">
            <h2 class="flex items-center gap-2 font-bold text-slate-900"><x-icon name="lucide:headset" class="size-5 text-brand-600" /> Butuh bantuan?</h2>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">Petugas kami siap membantu Anda menggunakan layanan klinik.</p>
            <p class="mt-4 text-xs text-slate-500">Senin&ndash;Sabtu &middot; 07.00&ndash;20.00</p>
            <a href="#" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-slate-50">
                <x-icon name="lucide:phone" class="size-4" /> Hubungi petugas klinik
            </a>
        </x-card>
    </section>
</div>
@endsection