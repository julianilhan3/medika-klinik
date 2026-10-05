@extends('layouts.portal')
@section('title', 'Booking Online')
@section('content')
@php $p = session('patient'); @endphp
<form method="POST" action="{{ route('booking.store') }}" class="mx-auto max-w-4xl"
      x-data="{
        step: 1, poli: '{{ old('poli') }}', doctor: '{{ old('doctor') }}', date: '{{ old('date') }}', time: '{{ old('time') }}', complaint: @js(old('complaint', '')),
        doctors: @js($doctors),
        get list() { return this.doctors.filter(d => d.poli === this.poli && d.status === 'Aktif') },
        get ok2() { return this.complaint.trim().length >= 5 },
        get ok1() { return this.poli && this.doctor && this.date && this.time },
        pickPoli(n) { if (this.poli !== n) { this.poli = n; this.doctor = ''; } },
        fmt(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-' }
      }">
    @csrf
    <input type="hidden" name="poli" :value="poli"><input type="hidden" name="doctor" :value="doctor"><input type="hidden" name="time" :value="time"><input type="hidden" name="complaint" :value="complaint">

    {{-- Judul --}}
    <div class="mb-6">
        <a href="{{ route('portal.home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 transition hover:-translate-x-0.5 hover:text-brand-600">
            <x-icon name="lucide:chevron-left" /> Kembali ke Beranda
        </a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Booking Online</h1>
                <p class="mt-1 text-sm text-slate-600">Atur kunjungan Anda dengan mudah, tanpa perlu mengantre di klinik.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-600">
                <x-icon name="lucide:calendar-days" /> Langkah <span x-text="step"></span> dari 3
            </span>
        </div>
    </div>

    {{-- Stepper (garis progres beranimasi) --}}
    <ol class="mb-6 flex items-center gap-2 px-1 sm:gap-3">
        @foreach (['Pilih Jadwal', 'Data Pasien', 'Konfirmasi'] as $i => $label)
            @php $n = $i + 1; @endphp
            <li class="flex items-center gap-2 sm:gap-3 {{ $loop->last ? '' : 'flex-1' }}">
                <span class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold transition-all duration-300 sm:size-10"
                      :class="step === {{ $n }} ? 'scale-105 bg-brand-600 text-white shadow-md shadow-brand-600/30' : (step > {{ $n }} ? 'bg-brand-50 text-brand-600' : 'bg-white text-slate-500 ring-1 ring-slate-200')">
                    <template x-if="step > {{ $n }}"><x-icon name="lucide:check" /></template>
                    <template x-if="step <= {{ $n }}"><span>{{ $n }}</span></template>
                </span>
                <span class="hidden text-sm transition sm:inline" :class="step === {{ $n }} ? 'font-medium text-brand-600' : 'text-slate-600'">{{ $label }}</span>
                @unless ($loop->last)
                    <span class="relative h-0.5 flex-1 overflow-hidden rounded bg-slate-300">
                        <span class="absolute inset-y-0 left-0 bg-brand-600 transition-all duration-500 ease-out" :style="'width:' + (step > {{ $n }} ? '100%' : '0%')"></span>
                    </span>
                @endunless
            </li>
        @endforeach
    </ol>

    <x-card class="p-5 sm:p-8">

        {{-- Step 1 --}}
        <div x-show="step === 1"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Pilih jadwal kunjungan</h2>
                <p class="text-sm text-slate-600">Pilih poli, dokter, dan waktu yang paling nyaman untuk Anda.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Dropdown Poli --}}
                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                    <label class="mb-1.5 block text-sm font-semibold">Poli</label>
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="flex h-12 w-full items-center justify-between rounded-xl border bg-white px-4 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-600/30"
                            :class="open ? 'border-brand-600' : 'border-slate-300 hover:border-slate-400'">
                        <span :class="poli ? 'text-slate-900' : 'text-slate-400'" x-text="poli || 'Pilih poli'"></span>
                        <x-icon name="lucide:chevron-down" class="text-slate-500 transition duration-200" ::class="open && 'rotate-180'" />
                    </button>
                    <ul x-show="open" x-cloak
                        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        class="absolute z-20 mt-2 max-h-60 w-full origin-top overflow-auto rounded-xl bg-white p-1 shadow-lg ring-1 ring-slate-200">
                        @foreach ($poli as $x)
                            <li>
                                <button type="button" data-name="{{ $x['name'] }}" @click="pickPoli($el.dataset.name); open = false"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm transition"
                                        :class="poli === $el.dataset.name ? 'bg-brand-50 font-medium text-brand-600' : 'text-slate-700 hover:bg-slate-50'">
                                    {{ $x['name'] }}
                                    <x-icon name="lucide:check" x-show="poli === $el.closest('button').dataset.name" x-cloak />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Dropdown Dokter (dari data $doctors) --}}
                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                    <label class="mb-1.5 block text-sm font-semibold">Pilih Dokter</label>
                    <button type="button" :disabled="!poli" @click="open = !open" :aria-expanded="open"
                            class="flex h-12 w-full items-center justify-between rounded-xl border bg-white px-4 text-left text-sm transition focus:outline-none focus:ring-2 focus:ring-brand-600/30 disabled:cursor-not-allowed disabled:bg-slate-50"
                            :class="open ? 'border-brand-600' : 'border-slate-300 hover:border-slate-400'">
                        <span :class="doctor ? 'text-slate-900' : 'text-slate-400'" x-text="doctor || (poli ? 'Pilih dokter' : 'Pilih poli terlebih dahulu')"></span>
                        <x-icon name="lucide:chevron-down" class="text-slate-500 transition duration-200" ::class="open && 'rotate-180'" />
                    </button>
                    <ul x-show="open && poli" x-cloak
                        x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        class="absolute z-20 mt-2 max-h-60 w-full origin-top overflow-auto rounded-xl bg-white p-1 shadow-lg ring-1 ring-slate-200">
                        <template x-for="d in list" :key="d.id">
                            <li>
                                <button type="button" @click="doctor = d.name; open = false"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm transition"
                                        :class="doctor === d.name ? 'bg-brand-50 font-medium text-brand-600' : 'text-slate-700 hover:bg-slate-50'">
                                    <span x-text="d.name"></span>
                                    <x-icon name="lucide:check" x-show="doctor === d.name" x-cloak />
                                </button>
                            </li>
                        </template>
                        <li x-show="list.length === 0" class="px-3 py-3 text-sm text-slate-500">Belum ada dokter aktif di poli ini.</li>
                    </ul>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Tanggal Kunjungan</label>
                <input type="date" name="date" x-model="date" min="{{ now()->toDateString() }}"
                       class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm transition hover:border-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30">
                <p x-show="date" x-cloak x-transition.opacity class="mt-1.5 text-xs text-slate-500" x-text="fmt(date)"></p>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold">Pilih Jam</h3>
                    <span class="text-xs text-slate-500 sm:text-sm">Waktu Indonesia Barat (WIB)</span>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($slots as $s)
                        <button type="button" @if (! $s['available']) disabled @endif @click="time = '{{ $s['time'] }}'"
                                :class="time === '{{ $s['time'] }}' ? 'scale-[1.03] border-brand-600 bg-brand-600 text-white shadow-md shadow-brand-600/30' : 'border-slate-200 bg-white text-slate-900 hover:-translate-y-0.5 hover:border-brand-600/50 hover:shadow-sm'"
                                class="rounded-lg border px-2 py-3 text-center transition-all duration-200 active:scale-95 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50 disabled:text-slate-300 disabled:hover:translate-y-0 disabled:hover:shadow-none">
                            <span class="flex items-center justify-center gap-1 font-semibold">
                                {{ $s['time'] }}
                                <span x-show="time === '{{ $s['time'] }}'" x-cloak x-transition:enter="transition duration-200" x-transition:enter-start="scale-0" x-transition:enter-end="scale-100"><x-icon name="lucide:check" /></span>
                            </span>
                            <span class="block text-xs {{ $s['available'] ? '' : 'line-through' }}" :class="time === '{{ $s['time'] }}' ? 'text-white/90' : 'text-slate-500'">
                                <span x-text="time === '{{ $s['time'] }}' ? 'Terpilih' : '{{ $s['available'] ? 'Tersedia' : 'Penuh' }}'"></span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <p class="flex items-center gap-2 rounded-lg bg-brand-50/70 px-4 py-3 text-sm text-slate-600">
                <x-icon name="lucide:info" class="shrink-0 text-brand-600" /> Datang 15 menit sebelum jadwal untuk melakukan daftar ulang.
            </p>

            <button type="button" :disabled="!ok1" @click="step = 2"
                    class="btn btn-primary group w-full transition active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50">
                Lanjutkan <x-icon name="lucide:arrow-right" class="transition group-hover:translate-x-1" />
            </button>
        </div>

        {{-- Step 2 --}}
        <div x-show="step === 2" x-cloak
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Data pasien</h2>
                <p class="text-sm text-slate-600">Periksa data Anda dan ceritakan keluhan utama.</p>
            </div>

            <dl class="divide-y divide-slate-100 rounded-xl bg-slate-50 px-5 text-sm">
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Nama</dt><dd class="text-right font-medium">{{ $p['name'] }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">NIK</dt><dd class="text-right font-medium">{{ $p['nik'] }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Telepon</dt><dd class="text-right font-medium">{{ $p['phone'] }}</dd></div>
            </dl>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">Keluhan utama</label>
                <textarea x-model="complaint" rows="4" maxlength="500"
                          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm transition hover:border-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30"
                          placeholder="Ceritakan keluhan Anda singkat agar dokter dapat bersiap"></textarea>
                <div class="mt-1 flex justify-between gap-3 text-xs text-slate-500">
                    <span>Dokter menggunakan informasi ini untuk menyetujui janji temu.</span>
                    <span class="shrink-0" x-text="complaint.length + '/500'"></span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-[auto_1fr]">
                <button type="button" @click="step = 1" class="btn btn-outline"><x-icon name="lucide:arrow-left" /> Kembali</button>
                <button type="button" :disabled="!ok2" @click="step = 3" class="btn btn-primary group transition active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50">
                    Lanjutkan <x-icon name="lucide:arrow-right" class="transition group-hover:translate-x-1" />
                </button>
            </div>
        </div>

        {{-- Step 3 --}}
        <div x-show="step === 3" x-cloak
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Konfirmasi Booking</h2>
                <p class="text-sm text-slate-600">Periksa kembali detail kunjungan sebelum mengonfirmasi.</p>
            </div>

            <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                <h3 class="flex items-center gap-2 font-semibold"><x-icon name="lucide:clipboard-list" class="text-brand-600" /> Detail Booking</h3>
                <dl class="mt-3 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Nama</dt><dd class="text-right font-medium">{{ $p['name'] }}</dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Poli</dt><dd class="text-right font-medium" x-text="poli"></dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Dokter</dt><dd class="text-right font-medium" x-text="doctor"></dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Tanggal</dt><dd class="text-right font-medium" x-text="fmt(date)"></dd></div>
                    <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">Jam</dt><dd class="text-right font-medium" x-text="time"></dd></div>
                    <div class="flex justify-between gap-6 py-2.5"><dt class="shrink-0 text-slate-500">Keluhan</dt><dd class="text-right font-medium" x-text="complaint"></dd></div>
                </dl>
            </div>

            <div class="space-y-2 text-center">
                <button class="btn btn-primary group w-full transition active:scale-[0.99]">Konfirmasi Booking <x-icon name="lucide:arrow-right" class="transition group-hover:translate-x-1" /></button>
                <button type="button" @click="step = 2" class="text-sm font-medium text-slate-600 transition hover:text-brand-600">Ubah</button>
            </div>
        </div>
    </x-card>
</form>

{{-- Modal duplikat tiket (kondisi session('duplicate') tidak diubah) --}}
@if (session('duplicate'))
<div x-data="{ open: true }" x-show="open" x-transition.opacity @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 grid place-items-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
    <div @click.outside="open = false"
         x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
         class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-red-600">Booking belum dapat dilanjutkan</p>
            <button type="button" @click="open = false" class="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Tutup"><x-icon name="lucide:x" class="text-xl" /></button>
        </div>
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
            <x-icon name="lucide:circle-alert" class="text-3xl text-red-500" />
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Anda sudah punya tiket di poli ini</h3>
            <p class="mt-1 text-sm text-slate-600">Silakan cek riwayat booking Anda atau pilih poli lain.</p>
        </div>
        <button type="button" @click="open = false" class="btn mt-4 w-full bg-red-600 text-white hover:bg-red-700">Tutup</button>
    </div>
</div>
@endif
@endsection