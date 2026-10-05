@extends('layouts.staff')
@section('title', 'Pemeriksaan')
@section('crumb', 'Antrean pasien / Pemeriksaan')
@section('heading', 'Form Pemeriksaan')
@section('subheading', 'Catat pemeriksaan pasien dengan lengkap dan terstruktur.')
@section('content')
@php
    $startStep = $errors->hasAny(['bp', 'pulse', 'temp', 'height', 'weight']) ? 1 : ($errors->hasAny(['complaint', 'anamnesis', 'physical']) ? 2 : ($errors->any() ? 3 : 1));
@endphp
<div class="mx-auto max-w-4xl space-y-5">
    @include('staff.doctor._patient-head')

    <form method="POST" action="{{ route('doctor.exam.store', $p['no']) }}" novalidate
          x-data="{ step: {{ $startStep }}, next(n) { for (const e of this.$refs['s' + this.step].querySelectorAll('input,textarea')) { if (!e.reportValidity()) return; } this.step = n; } }">
        @csrf
        <x-card class="p-5 sm:p-7">
            <ol class="mb-7 grid grid-cols-3 gap-2">
                @foreach ([['Tanda Vital', 'Sedang diisi'], ['Pemeriksaan', 'Belum diisi'], ['Diagnosis', 'Belum diisi']] as $i => [$t, $d])
                    <li class="flex items-center gap-3"><span :class="step >= {{ $i + 1 }} ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400'" class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold"><span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span><x-icon name="lucide:check" x-show="step > {{ $i + 1 }}" x-cloak class="text-base" /></span>
                        <div class="hidden leading-tight sm:block"><p class="text-sm font-semibold" :class="step === {{ $i + 1 }} ? 'text-brand-600' : 'text-slate-600'">{{ $t }}</p><p class="text-xs text-slate-400" x-text="step > {{ $i + 1 }} ? 'Selesai' : (step === {{ $i + 1 }} ? 'Sedang diisi' : 'Belum diisi')"></p></div></li>
                @endforeach
            </ol>

            {{-- 1. Tanda vital --}}
            <div x-ref="s1" x-show="step === 1">
                <h2 class="text-lg font-bold">Tanda Vital</h2><p class="text-sm text-slate-500">Masukkan hasil pengukuran terbaru sebelum memulai pemeriksaan.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ([['bp', 'Tekanan darah', 'mmHg', '120/80', 'text', 'Sistolik / diastolik'], ['pulse', 'Nadi', 'x/menit', '80', 'number', ''], ['temp', 'Suhu', 'C', '36.7', 'number', ''], ['height', 'TB (tinggi badan)', 'cm', '155', 'number', ''], ['weight', 'BB (berat badan)', 'kg', '58', 'number', '']] as [$n, $l, $unit, $ph, $type, $hint])
                        <div class="{{ $n === 'bp' ? 'sm:col-span-2' : '' }}">
                            <label class="label" for="e-{{ $n }}">{{ $l }}</label>
                            <div class="relative"><input id="e-{{ $n }}" name="{{ $n }}" type="{{ $type }}" @if ($type === 'number') step="0.1" @else pattern="\d{2,3}/\d{2,3}" @endif required placeholder="{{ $ph }}" value="{{ old($n) }}" class="input pr-20 @error($n) input-error @enderror"><span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-xs text-slate-400">{{ $unit }}</span></div>
                            @error($n)<p class="err">{{ $message }}</p>@elseif ($hint)<p class="hint">{{ $hint }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-600"><x-icon name="lucide:info" class="text-base" /> Pastikan satuan dan hasil pengukuran sesuai dengan kondisi pasien.</p>
                <button type="button" @click="next(2)" class="btn btn-primary mt-5 w-full">Lanjut <x-icon name="lucide:arrow-right" /></button>
            </div>

            {{-- 2. Pemeriksaan --}}
            <div x-ref="s2" x-show="step === 2" x-cloak class="space-y-4">
                <div><h2 class="text-lg font-bold">Pemeriksaan</h2><p class="text-sm text-slate-500">Keluhan, anamnesis, dan temuan pemeriksaan fisik.</p></div>
                <div><label class="label">Keluhan utama</label><textarea name="complaint" rows="2" required class="input @error('complaint') input-error @enderror">{{ old('complaint', $p['complaint']) }}</textarea>@error('complaint')<p class="err">{{ $message }}</p>@enderror</div>
                <div><label class="label">Anamnesis</label><textarea name="anamnesis" rows="3" class="input" placeholder="Riwayat keluhan, obat yang dikonsumsi, faktor pemicu">{{ old('anamnesis') }}</textarea></div>
                <div><label class="label">Pemeriksaan fisik</label><textarea name="physical" rows="3" class="input" placeholder="Temuan pemeriksaan fisik">{{ old('physical') }}</textarea></div>
                <div class="flex gap-3"><button type="button" @click="step = 1" class="btn btn-outline">Kembali</button><button type="button" @click="next(3)" class="btn btn-primary flex-1">Lanjut <x-icon name="lucide:arrow-right" /></button></div>
            </div>

            {{-- 3. Diagnosis --}}
            <div x-ref="s3" x-show="step === 3" x-cloak class="space-y-4">
                <div><h2 class="text-lg font-bold">Diagnosis</h2><p class="text-sm text-slate-500">Tetapkan diagnosis lalu lanjutkan ke resep.</p></div>
                <div class="grid gap-4 sm:grid-cols-[1fr_9rem]"><x-field label="Diagnosis" name="diagnosis" placeholder="Contoh: Hipertensi esensial" required /><x-field label="Kode ICD-10" name="icd" placeholder="I10" /></div>
                <div><label class="label">Edukasi dan rencana kontrol</label><textarea name="education" rows="3" class="input" placeholder="Anjuran untuk pasien dan jadwal kontrol">{{ old('education') }}</textarea></div>
                <div class="flex gap-3"><button type="button" @click="step = 2" class="btn btn-outline">Kembali</button><button class="btn btn-primary flex-1"><x-icon name="lucide:save" /> Simpan dan buat resep</button></div>
            </div>
        </x-card>
    </form>
    <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:lock" class="text-base" /> Data pemeriksaan tersimpan aman dalam rekam medis pasien.</p>
</div>
@endsection
