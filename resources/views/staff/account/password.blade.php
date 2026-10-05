@extends('layouts.staff')
@section('title', 'Ganti Password')
@section('crumb', 'Pengaturan akun')
@section('heading', 'Ganti Password')
@section('subheading', 'Perbarui password secara berkala untuk menjaga keamanan akun Anda.')
@section('content')
@php
    $done = session('password_changed');
    $isPatient = ! request()->is('staff/*');
    
    
    $defaultAction = $isPatient ? route('portal.password.update') : route('staff.password.update'); 
    $action = isset($action) ? $action : $defaultAction;

    $homeRoute = $isPatient ? 'portal.home' : config('clinic.roles.'.session('staff.role').'.home');
    $homeLabel = $isPatient ? 'Beranda' : 'Dashboard '.config('clinic.roles.'.session('staff.role').'.label');
@endphp
<div class="grid gap-6 lg:grid-cols-[minmax(0,32rem)_1fr]">
    <x-card class="p-6">
        @if ($done)
            <span class="grid size-12 place-items-center rounded-full bg-emerald-50 text-emerald-600"><x-icon name="lucide:check" class="text-2xl" /></span>
            <h2 class="mt-4 text-xl font-bold text-slate-900">Password berhasil diganti</h2>
            <p class="mt-1 text-sm text-slate-500">Akun Anda kini menggunakan password baru. Gunakan password tersebut saat login berikutnya.</p>
            <a href="{{ route($homeRoute) }}" class="btn btn-primary mt-5 w-full">Kembali ke {{ $homeLabel }}</a>
        @else
            <h2 class="text-lg font-bold text-slate-900">Perbarui password Anda</h2>
            <p class="text-sm text-slate-500">Lengkapi semua isian di bawah ini.</p>
            @if ($errors->any())
                <div class="mt-4 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm text-red-700"><x-icon name="lucide:alert-circle" class="mt-0.5" />
                    <div><p class="font-semibold">Password belum dapat disimpan</p><p>Perbaiki isian yang ditandai di bawah ini.</p></div></div>
            @endif
            <form method="POST" action="{{ $action }}" class="mt-5 space-y-4">@csrf @method('PUT')
                <x-field label="Password Lama" name="current_password" type="password" placeholder="Masukkan password lama" />
                <x-field label="Password Baru" name="password" type="password" placeholder="Buat password baru" hint="Minimal 8 karakter, dengan huruf dan angka." />
                <x-field label="Konfirmasi Password" name="password_confirmation" type="password" placeholder="Ulangi password baru" />
                <button class="btn btn-primary w-full">Simpan</button>
            </form>
            <p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:lock" /> Jangan bagikan password Anda kepada siapa pun.</p>
        @endif
    </x-card>
    <div class="px-2">
        <span class="grid size-10 place-items-center rounded-lg bg-white text-brand-600 shadow-sm"><x-icon name="lucide:shield-check" /></span>
        <h3 class="mt-4 font-semibold text-slate-900">Password yang aman</h3>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
            @foreach (['Gunakan minimal 8 karakter.', 'Gabungkan huruf dan angka.', 'Hindari nama atau tanggal lahir.', 'Jangan gunakan password lama.'] as $tip)
                <li class="flex items-center gap-2"><x-icon name="lucide:check" class="text-brand-600" /> {{ $tip }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
