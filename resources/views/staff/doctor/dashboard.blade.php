@extends('layouts.staff')
@section('title', 'Ringkasan')
@section('crumb', 'Ringkasan')
@section('heading', 'Ringkasan')
@section('subheading', 'Gambaran praktik Anda hari ini.')
@section('actions')<a href="{{ route('doctor.queue') }}" class="btn btn-primary"><x-icon name="lucide:users" /> Buka antrean pasien</a>@endsection
@section('content')
<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    <x-stat label="Total pasien" :value="$stats['total']" icon="lucide:users" />
    <x-stat label="Menunggu" :value="$stats['waiting']" icon="lucide:clock" tone="amber" />
    <x-stat label="Dipanggil" :value="$stats['called']" icon="lucide:volume-2" tone="blue" />
    <x-stat label="Selesai" :value="$stats['done']" icon="lucide:check-circle-2" tone="green" />
    <x-stat label="Tidak hadir" :value="$stats['absent']" icon="lucide:user-x" tone="red" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <x-card class="p-5">
        <div class="flex items-center justify-between"><h2 class="font-semibold">Permintaan janji temu</h2><x-badge :text="count($requests).' menunggu'" tone="amber" /></div>
        <ul class="mt-3 divide-y divide-slate-100">
        @forelse ($requests as $a)
            <li class="py-3" x-data="{ reject: false }">
                <p class="text-sm font-medium">{{ $a['patient'] }} <span class="font-normal text-slate-500">- {{ $a['age'] }} th</span></p>
                <p class="text-xs text-slate-500">{{ $a['date'] }}, {{ $a['time'] }} WIB</p>
                <p class="mt-1 text-sm text-slate-600">{{ $a['complaint'] }}</p>
                <form method="POST" action="{{ route('doctor.appointments.decide', $a['id']) }}" class="mt-2">@csrf
                    <input x-show="reject" x-cloak name="reason" class="input mb-2" placeholder="Alasan penolakan">
                    <div class="flex gap-2">
                        <button name="decision" value="approve" x-show="!reject" class="btn btn-primary btn-sm">Setujui</button>
                        <button type="button" x-show="!reject" @click="reject = true" class="btn btn-outline btn-sm text-red-600">Tolak</button>
                        <button name="decision" value="reject" x-show="reject" x-cloak class="btn btn-danger btn-sm">Kirim penolakan</button>
                        <button type="button" x-show="reject" x-cloak @click="reject = false" class="btn btn-outline btn-sm">Batal</button>
                    </div>
                </form>
            </li>
        @empty
            <x-empty icon="lucide:calendar-check" title="Tidak ada permintaan" text="Janji temu baru dari pasien akan muncul di sini." />
        @endforelse
        </ul>
    </x-card>

    <div class="space-y-6">
        @if ($next)
        <x-card class="p-5">
            <h2 class="font-semibold">Pasien berikutnya</h2>
            <div class="mt-3 flex items-center justify-between gap-3"><div><p class="font-medium">{{ $next['no'] }} - {{ $next['patient'] }}</p><p class="text-sm text-slate-500">{{ $next['complaint'] }}</p></div>
                <a href="{{ route('doctor.queue') }}" class="btn btn-soft btn-sm">Ke antrean</a></div>
        </x-card>
        @endif
        <x-card class="p-5">
            <h2 class="font-semibold">Pesan dari apotek</h2>
            @forelse ($alerts as $al)
                <div class="mt-3 flex gap-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"><x-icon name="lucide:bell-ring" class="mt-0.5 text-lg" />
                    <div><p class="font-semibold">{{ $al['code'] }} - {{ $al['patient'] }} <span class="font-normal text-amber-700">({{ $al['time'] }})</span></p><p>{{ $al['note'] }}</p></div></div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Tidak ada catatan dari apotek.</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
