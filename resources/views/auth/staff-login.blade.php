@extends('layouts.auth')
@section('title', 'Login Staf')
@section('top-right')
    <a href="{{ route('portal.login') }}" class="flex items-center gap-4 text-xs text-slate-500 transition-colors hover:text-blue-600 px-8">
        <x-icon name="lucide:user" class="size-4" /> <span class="mt-2 text-sm">Pasien</span>
    </a>
@endsection



@section('content')




<div class="grid w-full max-w-5xl items-center gap-10 lg:grid-cols-2">
    <div class="hidden lg:block">
        <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="lucide:heart-pulse" class="text-2xl" /></span>
        <p class="mt-6 text-sm font-semibold text-brand-600">Ruang kerja staf</p>
        <h1 class="mt-2 text-4xl font-bold leading-tight text-navy-800">Akses aman untuk layanan yang lebih baik.</h1>
        <p class="mt-4 max-w-md text-slate-600">Satu pintu masuk untuk mendukung pelayanan sehari-hari di Medika Klinik.</p>
        <div class="mt-5 flex gap-2">@foreach (['Admin', 'Dokter', 'Apoteker'] as $r)<span class="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm">{{ $r }}</span>@endforeach</div>
        <p class="mt-8 flex max-w-sm gap-2 text-xs text-slate-500"><x-icon name="lucide:lock" class="mt-0.5" /> Gunakan akun pribadi Anda. Jangan bagikan username dan password kepada orang lain.</p>
    </div>

    <div>
        <x-card class="mx-auto max-w-md p-7">
            <h2 class="text-2xl font-bold text-slate-900">Login Staf</h2>
            <p class="mt-1 text-sm text-slate-500">Selamat datang kembali. Masuk dengan akun staf Anda untuk melanjutkan.</p>

            @if (session('login_error'))
                <div class="mt-5 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm text-red-700">
                    <x-icon name="lucide:alert-circle" class="mt-0.5" />
                    <div><p class="font-semibold">{{ session('login_error') }}</p><p class="text-red-600/80">Periksa kembali data Anda, lalu coba lagi.</p></div>
                </div>
            @endif

            <form method="POST" action="{{ route('staff.login.submit') }}" class="mt-5 space-y-4">@csrf
                <x-field label="Username" name="username" placeholder="Masukkan username" autocomplete="username" autofocus />
                <x-field label="Password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" />
                <button class="btn btn-primary w-full">Masuk <x-icon name="lucide:arrow-right" /></button>
            </form>
            <p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:shield-check" /> Akses hanya untuk staf Medika Klinik.</p>
        </x-card>
        <p class="mx-auto mt-3 max-w-md text-xs text-slate-500">Lupa password? Hubungi admin klinik.</p>

       
    </div>
</div>
@endsection
