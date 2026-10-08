@extends('layouts.staff')
@section('title', 'Beranda')
@section('crumb', 'Beranda')
@section('heading', 'Beranda')
@section('subheading', 'Ringkasan operasional klinik hari ini.')
@section('actions')
<a href="{{ route('admin.patients.create') }}" class="btn btn-primary w-full justify-center sm:w-auto">
    <x-icon name="lucide:user-plus" />
    <span class="sm:hidden">Daftar pasien</span>
    <span class="hidden sm:inline">Daftarkan pasien</span>
</a>
@endsection
@section('content')
{{-- Statistik: 2 kolom di HP, 4 kolom di layar lebar --}}
<div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <x-stat label="Pasien hari ini" :value="$data['stats']['patients_today']" hint="Booking dan walk-in" icon="lucide:users" />
    <x-stat label="Dokter bertugas" :value="$data['stats']['doctors_on_duty']" hint="Sesuai jadwal hari ini" icon="lucide:stethoscope" tone="green" />
    <x-stat label="Resep menunggu" :value="$data['stats']['prescriptions_pending']" hint="Di apotek" icon="lucide:pill" tone="amber" />
    <x-stat label="Pasien baru" :value="$data['stats']['new_patients']" hint="Terdaftar hari ini" icon="lucide:user-plus" tone="blue" />
</div>

<div class="mt-4 grid gap-4 sm:mt-6 sm:gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    {{-- Antrean terbaru --}}
    <x-card class="min-w-0">
        <div class="flex items-center justify-between gap-3 p-4 pb-3 sm:p-5 sm:pb-3">
            <h2 class="font-semibold">Antrean terbaru</h2>
            <a href="{{ route('admin.queue') }}" class="shrink-0 text-sm font-medium text-brand-600 hover:underline">Lihat semua</a>
        </div>

        {{-- HP: tampilan kartu --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @forelse ($queue as $q)
                <li class="flex items-start justify-between gap-3 px-4 py-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $q['no'] }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $q['patient'] }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $q['doctor'] }} &middot; {{ $q['time'] }}</p>
                        </div>
                    </div>
                    <div class="shrink-0"><x-badge :text="$q['status']" /></div>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-slate-500">Belum ada antrean.</li>
            @endforelse
        </ul>

        {{-- Tablet ke atas: tabel --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>No</th><th>Pasien</th><th>Dokter</th><th>Jam</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($queue as $q)
                    <tr>
                        <td class="font-medium">{{ $q['no'] }}</td>
                        <td>{{ $q['patient'] }}</td>
                        <td>{{ $q['doctor'] }}</td>
                        <td>{{ $q['time'] }}</td>
                        <td><x-badge :text="$q['status']" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-sm text-slate-500">Belum ada antrean.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Dokter bertugas --}}
    <x-card class="min-w-0 p-4 sm:p-5">
        <h2 class="font-semibold">Dokter bertugas</h2>
        <ul class="mt-3 divide-y divide-slate-100">
            @forelse ($data['today'] as $s)
                <li class="py-3">
                    <p class="text-sm font-medium">{{ $s['doctor'] }}</p>
                    <p class="text-xs text-slate-500">{{ $s['poli'] }} &middot; {{ $s['room'] }}</p>
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500">Tidak ada dokter bertugas hari ini.</li>
            @endforelse
        </ul>
    </x-card>
</div>
@endsection