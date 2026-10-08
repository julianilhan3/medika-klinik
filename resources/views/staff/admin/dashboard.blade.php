@extends('layouts.staff')
@section('title', 'Beranda')
@section('crumb', 'Beranda')
@section('heading', 'Beranda')
@section('subheading', 'Ringkasan operasional klinik hari ini.')
@section('actions')<a href="{{ route('admin.patients.create') }}" class="btn btn-primary"><x-icon name="lucide:user-plus" /> Daftarkan pasien</a>@endsection
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Pasien hari ini" :value="$data['stats']['patients_today']" hint="Booking dan walk-in" icon="lucide:users" />
    <x-stat label="Dokter bertugas" :value="$data['stats']['doctors_on_duty']" hint="Sesuai jadwal hari ini" icon="lucide:stethoscope" tone="green" />
    <x-stat label="Resep menunggu" :value="$data['stats']['prescriptions_pending']" hint="Di apotek" icon="lucide:pill" tone="amber" />
    <x-stat label="Pasien baru" :value="$data['stats']['new_patients']" hint="Terdaftar hari ini" icon="lucide:user-plus" tone="blue" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_22rem]">
    <x-card>
        <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Antrean terbaru</h2><a href="{{ route('admin.queue') }}" class="text-sm font-medium text-brand-600 hover:underline">Lihat semua</a></div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>No</th><th>Pasien</th><th>Dokter</th><th>Jam</th><th>Status</th></tr></thead>
            <tbody>@foreach ($queue as $q)<tr><td class="font-medium">{{ $q['no'] }}</td><td>{{ $q['patient'] }}</td><td>{{ $q['doctor'] }}</td><td>{{ $q['time'] }}</td><td><x-badge :text="$q['status']" /></td></tr>@endforeach</tbody>
        </table></div>
    </x-card>
    <x-card class="p-5">
        <h2 class="font-semibold">Dokter bertugas</h2>
        <ul class="mt-3 divide-y divide-slate-100">
            @foreach ($data['today'] as $s)
                <li class="py-3"><p class="text-sm font-medium">{{ $s['doctor'] }}</p><p class="text-xs text-slate-500">{{ $s['poli'] }} &middot; {{ $s['room'] }}</p></li>
            @endforeach
        </ul>
    </x-card>
</div>
@endsection
