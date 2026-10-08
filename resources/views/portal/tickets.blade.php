@extends('layouts.portal')
@section('title', 'Tiket & Antrean')
@section('content')
<div x-data="{ tab: 'active' }" class="mx-auto max-w-3xl">
    <x-card class="space-y-5 p-5 sm:p-8">
        @include('partials.portal-stepper', ['current' => 3, 'steps' => ['Pilih jadwal', 'Konfirmasi booking', 'Tiket & antrean']])

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Tahap 3 &middot; Tiket & Antrean</p>
            <h1 class="text-2xl font-bold">Tiket & Antrean</h1>
            <p class="text-sm text-slate-600">Lihat tiket kunjungan Anda dan pantau antrean secara langsung.</p>
        </div>

        <div class="flex gap-6 border-b border-slate-200 text-sm font-medium">
            <button @click="tab = 'active'" :class="tab === 'active' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 pb-2 transition">Tiket Aktif <span class="ml-1 rounded-full bg-brand-50 px-1.5 text-xs">{{ count($active) }}</span></button>
            <button @click="tab = 'history'" :class="tab === 'history' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 pb-2 transition">Riwayat</button>
        </div>

        @foreach (['active' => $active, 'history' => $history] as $key => $list)
        <div x-show="tab === '{{ $key }}'" @if ($key === 'history') x-cloak @endif x-transition.opacity class="space-y-4">
            @forelse ($list as $t)
                <div class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                        <div>
                            <div class="flex items-center gap-2"><p class="font-semibold">Kunjungan rawat jalan</p><x-badge :text="$t['status']" /></div>
                            <p class="text-xs text-slate-500">Booking {{ $t['code'] }}</p>
                            <dl class="mt-3 space-y-1.5 text-sm text-slate-600">
                                <div class="flex gap-2"><x-icon name="lucide:user-round" class="mt-0.5" /> {{ $t['doctor'] }}</div>
                                <div class="flex gap-2"><x-icon name="lucide:stethoscope" class="mt-0.5" /> {{ $t['poli'] }}</div>
                                <div class="flex gap-2"><x-icon name="lucide:calendar" class="mt-0.5" /> {{ $t['date'] }} &middot; {{ $t['time'] }} WIB</div>
                            </dl>
                        </div>
                        <div class="rounded-xl bg-brand-50 px-6 py-4 text-center">
                            <p class="text-xs text-slate-500">Nomor antrean Anda</p>
                            <p class="text-4xl font-bold text-brand-600">{{ $t['queue'] }}</p>
                        </div>
                    </div>

                    <p class="mt-4 flex items-center gap-2 rounded-lg bg-brand-50/60 p-3 text-xs text-slate-600"><x-icon name="lucide:info" /> Datang 15 menit sebelum jam kunjungan dan tunjukkan tiket ini kepada petugas.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @if (in_array($t['status'], ['Aktif', 'Menunggu Persetujuan']))
                            <form method="POST" action="{{ route('tickets.cancel', $t['code']) }}" onsubmit="return confirm('Batalkan booking ini?')">@csrf @method('DELETE')
                                <button class="btn w-full bg-red-600 text-white hover:bg-red-700"><x-icon name="lucide:trash-2" /> Batalkan Booking</button>
                            </form>
                        @endif
                        <a href="{{ route('tickets.show', $t['code']) }}" class="btn btn-primary w-full {{ in_array($t['status'], ['Aktif', 'Menunggu Persetujuan']) ? '' : 'sm:col-span-2' }}"><x-icon name="lucide:eye" /> Lihat Detail</a>
                    </div>
                </div>
            @empty
                <x-empty icon="lucide:ticket" title="Belum ada tiket" text="Booking kunjungan Anda akan muncul di sini." />
            @endforelse
        </div>
        @endforeach
    </x-card>
</div>
@endsection