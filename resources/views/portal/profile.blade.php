@extends('layouts.portal')
@section('title', 'Profil Saya')
@section('content')
@php
    $nm = $p['name'] ?? 'Pasien';
    $ini = collect(explode(' ', $nm))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $ro = 'h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-500';
    $sel = 'h-12 w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 pr-10 text-sm transition hover:border-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
@endphp

<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('portal.home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 transition hover:-translate-x-0.5 hover:text-brand-600">
            <x-icon name="lucide:chevron-left" /> Kembali ke Beranda
        </a>
        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-brand-600">Profil Saya</p>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Profil Saya</h1>
        <p class="mt-1 text-sm text-slate-600">Data yang akurat membantu dokter dan petugas melayani Anda.</p>
    </div>

    <form method="POST" action="{{ route('portal.profile.update') }}" class="grid gap-5" x-data="{ saving: false }" @submit="saving = true">@csrf @method('PUT')

        {{-- Ringkasan pasien --}}
        <div x-data="{ s: false }" x-init="setTimeout(() => s = true, 50)" :class="s ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'" class="transition duration-500">
            <x-card class="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <span class="grid size-12 place-items-center rounded-full bg-brand-50 text-base font-semibold text-brand-600">{{ $ini }}</span>
                    <div>
                        <p class="font-semibold text-slate-900">{{ $nm }}</p>
                        <p class="text-xs text-slate-500">No. Rekam Medis: {{ $p['rm'] }}</p>
                    </div>
                </div>
                <span class="hidden items-center gap-1 text-xs text-slate-500 sm:flex"><x-icon name="lucide:shield-check" /> Data medis pribadi</span>
            </x-card>
        </div>

        {{-- Data diri --}}
        <div x-data="{ s: false }" x-init="setTimeout(() => s = true, 130)" :class="s ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'" class="transition duration-500">
            <x-card class="p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:user-round" class="text-xl" /></span>
                    <div>
                        <h2 class="font-semibold text-slate-900">Data diri</h2>
                        <p class="text-sm text-slate-500">NIK dan nama tidak dapat diubah sendiri. Hubungi pendaftaran jika ada kesalahan.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Nama lengkap</label><div class="relative"><input class="input {{ $ro }}" value="{{ $p['name'] }}" disabled><x-icon name="lucide:lock" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" /></div></div>
                    <div><label class="label">NIK</label><div class="relative"><input class="input {{ $ro }}" value="{{ $p['nik'] }}" disabled><x-icon name="lucide:lock" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" /></div></div>
                    <div><label class="label">Tanggal lahir</label><div class="relative"><input class="input {{ $ro }}" value="{{ \Illuminate\Support\Carbon::parse($p['birth'])->translatedFormat('j F Y') }}" disabled><x-icon name="lucide:calendar" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" /></div></div>
                    <div><label class="label">Nomor rekam medis</label><div class="relative"><input class="input {{ $ro }}" value="{{ $p['rm'] }}" disabled><x-icon name="lucide:lock" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" /></div></div>
                    <x-field label="Nomor telepon" name="phone" type="tel" :value="$p['phone']" />
                    <x-field label="Email (opsional)" name="email" type="email" :value="$p['email']" />
                    <x-field label="Alamat" name="address" :value="$p['address']" class="sm:col-span-2" />
                </div>
            </x-card>
        </div>

        {{-- Data kesehatan --}}
        <div x-data="{ s: false }" x-init="setTimeout(() => s = true, 210)" :class="s ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'" class="transition duration-500">
            <x-card class="p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:heart-pulse" class="text-xl" /></span>
                    <h2 class="font-semibold text-slate-900">Data kesehatan</h2>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Golongan darah</label>
                        <div class="relative">
                            <select name="blood_type" class="input {{ $sel }}"><option value="">Belum diketahui</option>@foreach (['A', 'B', 'AB', 'O'] as $g)<option @selected(old('blood_type', $p['blood_type']) === $g)>{{ $g }}</option>@endforeach</select>
                            <x-icon name="lucide:chevron-down" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500" />
                        </div>
                    </div>
                    <x-field label="Alergi obat atau makanan" name="allergies" :value="$p['allergies']" hint="Dokter dan apoteker melihat informasi ini saat meresepkan." />
                </div>
            </x-card>
        </div>

        {{-- Kontak darurat --}}
        <div x-data="{ s: false }" x-init="setTimeout(() => s = true, 290)" :class="s ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'" class="transition duration-500">
            <x-card class="p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:phone-call" class="text-xl" /></span>
                    <h2 class="font-semibold text-slate-900">Kontak darurat</h2>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <x-field label="Nama" name="emergency_name" :value="$p['emergency']['name']" />
                    <x-field label="Hubungan" name="emergency_relation" :value="$p['emergency']['relation']" />
                    <x-field label="Telepon" name="emergency_phone" type="tel" :value="$p['emergency']['phone']" />
                </div>
            </x-card>
        </div>

        {{-- Pembayaran --}}
        <div x-data="{ s: false }" x-init="setTimeout(() => s = true, 370)" :class="s ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'" class="transition duration-500">
            <x-card class="p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:wallet-cards" class="text-xl" /></span>
                    <h2 class="font-semibold text-slate-900">Pembayaran</h2>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Jenis pembayaran</label>
                        <div class="relative">
                            <select name="insurance_type" class="input {{ $sel }}">@foreach (['Umum', 'BPJS Kesehatan', 'Asuransi Swasta'] as $t)<option @selected(old('insurance_type', $p['insurance']['type']) === $t)>{{ $t }}</option>@endforeach</select>
                            <x-icon name="lucide:chevron-down" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500" />
                        </div>
                    </div>
                    <x-field label="Nomor kepesertaan" name="insurance_number" :value="$p['insurance']['number']" />
                </div>
            </x-card>
        </div>

        {{-- Keamanan akun (tautan ke halaman yang sudah ada) --}}
        <a href="{{ route('portal.password') }}" class="group flex items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:ring-brand-600/40 sm:p-5">
            <span class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:shield-check" class="text-xl" /></span>
                <span><span class="block font-semibold text-slate-900">Keamanan akun</span><span class="block text-sm text-slate-500">Ubah kata sandi akun Anda.</span></span>
            </span>
            <x-icon name="lucide:arrow-right" class="text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-600" />
        </a>

        {{-- Simpan --}}
        <div class="flex justify-end">
            <button class="btn btn-primary w-full transition active:scale-[0.99] disabled:opacity-60 sm:w-auto" :disabled="saving">
                <x-icon name="lucide:save" x-show="!saving" />
                <x-icon name="lucide:loader-circle" class="animate-spin" x-show="saving" x-cloak />
                <span x-text="saving ? 'Menyimpan...' : 'Simpan perubahan'"></span>
            </button>
        </div>
    </form>
</div>
@endsection