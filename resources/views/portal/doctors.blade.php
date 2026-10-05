@extends('layouts.portal')
@section('title', 'Jadwal Dokter')
@section('content')
<div x-data="{ q: @js($q), match(n) { return !this.q || n.toLowerCase().includes(this.q.toLowerCase()) } }" class="mx-auto max-w-4xl">
    <x-card class="space-y-6 p-5 sm:p-8">
        @include('partials.portal-stepper', ['current' => 1])

        <div>
            <h1 class="text-2xl font-bold">Jadwal Dokter</h1>
            <p class="text-sm text-slate-600">Temukan jadwal praktik dan pilih dokter untuk kunjungan Anda.</p>
        </div>

        <form method="GET" action="{{ route('portal.doctors') }}" class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium">Poli
                <select name="poli" onchange="this.form.submit()" class="input mt-1.5 w-full">
                    <option value="">Semua poli</option>
                    @foreach ($polis as $p)
                        <option value="{{ $p }}" @selected($poli === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium">Tanggal
                <input type="date" name="date" value="{{ $date }}" min="{{ now()->toDateString() }}" onchange="this.form.submit()" class="input mt-1.5 w-full">
            </label>
            <label class="relative text-sm font-medium sm:col-span-2">Cari nama dokter
                <x-icon name="lucide:search" class="absolute bottom-3 left-3 text-slate-400" />
                <input x-model="q" type="search" placeholder="Contoh: dr. Nadia" class="input mt-1.5 w-full pl-10">
            </label>
        </form>

        <div class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            <x-icon name="lucide:circle-check" class="mt-0.5" />
            <div>
                <p class="font-semibold">Jadwal berhasil dimuat</p>
                <p>Silakan pilih dokter dan jam praktik yang sesuai untuk melanjutkan.</p>
            </div>
        </div>

        <div class="flex items-center justify-between text-sm">
            <p class="font-semibold">{{ $doctors->count() }} dokter &bull; {{ $poli ?: 'Semua poli' }}</p>
            <p class="text-slate-500">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, j F Y') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($doctors as $i => $d)
                <article x-data="{ show: false }" x-init="setTimeout(() => show = true, {{ $i * 80 }})"
                         x-show="show && match(@js($d['name']))" x-transition:enter="transition duration-300"
                         x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                         class="relative rounded-2xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-md">

                    <span class="absolute right-3 top-3 rounded-md px-2 py-0.5 text-xs font-semibold {{ $d['available'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                        {{ $d['available'] ? 'Tersedia' : 'Penuh' }}
                    </span>

                    {{-- Foto diambil otomatis dari unggahan admin (berdasarkan nama dokter) --}}
                    <x-doctor-avatar :name="$d['name']" size="size-16" />

                    <h3 class="mt-3 font-semibold">{{ $d['name'] }}</h3>
                    <p class="text-sm text-slate-500">{{ $d['poli'] }}</p>
                    <p class="mt-2 flex items-center gap-2 text-sm text-slate-600"><x-icon name="lucide:clock" /> {{ $d['time'] }} WIB</p>
                    <p class="text-xs text-slate-500">Sisa kuota: {{ $d['quota'] }}</p>

                    @if ($d['available'])
                        <a href="{{ route('booking', ['doctor' => $d['id'], 'date' => $date]) }}" class="btn btn-primary mt-4 w-full">Booking</a>
                    @else
                        <button disabled class="btn mt-4 w-full cursor-not-allowed opacity-50">Booking</button>
                    @endif
                </article>
            @empty
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-empty icon="lucide:search-x" title="Dokter tidak ditemukan" text="Coba ubah poli atau tanggal." />
                </div>
            @endforelse
        </div>

        <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:info" /> Pilih "Booking" untuk melanjutkan ke pengisian data pasien. Booking belum dikonfirmasi.</p>
    </x-card>
</div>
@endsection