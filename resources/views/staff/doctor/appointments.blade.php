@extends('layouts.staff')
@section('title', 'Persetujuan Janji Temu')
@section('crumb', 'Persetujuan janji temu')
@section('heading', 'Persetujuan Janji Temu')
@section('subheading', 'Tinjau permintaan kunjungan dari pasien lalu setujui atau tolak.')
@section('content')
<div class="space-y-4">
@foreach ($rows as $a)
    <x-card class="p-5" x-data="{ reject: false }">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-semibold text-slate-900">{{ $a['patient'] }} <span class="font-normal text-slate-500">&middot; {{ $a['age'] }} tahun</span></p>
                <p class="mt-1 flex items-center gap-2 text-sm text-slate-500"><x-icon name="lucide:calendar" class="text-base" /> {{ $a['date'] }}, {{ $a['time'] }} WIB &middot; {{ $a['poli'] }}</p>
            </div>
            <x-badge :text="$a['status']" />
        </div>
        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600"><span class="font-medium text-slate-700">Keluhan:</span> {{ $a['complaint'] }}</p>
        @if ($a['status'] === 'Menunggu')
        <form method="POST" action="{{ route('doctor.appointments.decide', $a['id']) }}" class="mt-4">@csrf
            <div x-show="reject" x-cloak class="mb-3"><label class="label">Alasan penolakan</label><input name="reason" class="input" placeholder="Contoh: jadwal penuh, pilih tanggal lain"></div>
            <div class="flex flex-wrap gap-2">
                <button name="decision" value="approve" x-show="!reject" class="btn btn-primary btn-sm"><x-icon name="lucide:check" class="text-base" /> Setujui</button>
                <button type="button" x-show="!reject" @click="reject = true" class="btn btn-outline btn-sm text-red-600"><x-icon name="lucide:x" class="text-base" /> Tolak</button>
                <button name="decision" value="reject" x-show="reject" x-cloak class="btn btn-danger btn-sm">Kirim penolakan</button>
                <button type="button" x-show="reject" x-cloak @click="reject = false" class="btn btn-outline btn-sm">Batal</button>
            </div>
        </form>
        @endif
    </x-card>
@endforeach
</div>
@endsection
