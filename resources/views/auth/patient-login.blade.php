@extends('layouts.auth')
@section('title', 'Masuk')
@section('top-right')<a href="{{ route('staff.login') }}" class="flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600"><x-icon name="lucide:shield" />-> Portal staf</a>@endsection
@section('content')
<x-card class="w-full max-w-sm p-7">
    <h1 class="text-xl font-bold text-slate-900">Masuk ke Akun Anda</h1>
    <p class="mt-1 text-sm text-slate-500">Gunakan NIK dan password Anda.</p>
    @include('partials.flash', ['hideErrors' => true])
    <form method="POST" action="{{ route('portal.login.submit') }}" class="mt-5 space-y-4">@csrf
        <x-field label="NIK" name="nik" inputmode="numeric" maxlength="16" placeholder="Masukkan 16 digit NIK" />
        <x-field label="Password" name="password" type="password" placeholder="Password" />
        <button class="btn btn-primary w-full">Masuk</button>
    </form>
    <p class="mt-4 text-center text-xs text-slate-500">Lupa password? Hubungi admin klinik.</p>
    <p class="mt-4 border-t border-slate-100 pt-4 text-center text-sm text-slate-600">Belum punya akun? <a class="font-medium text-brand-600 hover:underline" href="{{ route('portal.register') }}">Daftar di sini</a></p>
    <p class="mt-3 text-center text-xs text-slate-400">Demo: NIK 3273010310980001 / password123</p>
</x-card>
@endsection
