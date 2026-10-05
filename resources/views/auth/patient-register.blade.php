@extends('layouts.auth')
@section('title', 'Daftar Akun')
@section('content')
<x-card class="w-full max-w-sm p-7">
    <h1 class="text-xl font-bold text-slate-900">Daftar Akun</h1>
    <p class="mt-1 text-sm text-slate-500">Isi data diri Anda untuk mendaftar.</p>
    <form method="POST" action="{{ route('portal.register.submit') }}" class="mt-5 space-y-4">@csrf
        <x-field label="NIK" name="nik" inputmode="numeric" maxlength="16" placeholder="Masukkan 16 digit NIK" />
        <x-field label="Nama lengkap" name="name" placeholder="Masukkan nama lengkap" />
        <x-field label="Tanggal lahir" name="birth" type="date" />
        <div><label class="label">Jenis kelamin</label><select name="gender" class="input @error('gender') input-error @enderror"><option value="">Pilih</option><option @selected(old('gender') === 'Laki-laki')>Laki-laki</option><option @selected(old('gender') === 'Perempuan')>Perempuan</option></select>@error('gender')<p class="err">{{ $message }}</p>@enderror</div>
        <x-field label="Nomor telepon" name="phone" type="tel" placeholder="08xx-xxxx-xxxx" />
        <x-field label="Password" name="password" type="password" placeholder="Minimal 8 karakter" />
        <div>
            <label class="flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" class="mt-0.5 rounded border-slate-300 text-brand-600"> Saya menyetujui Syarat dan Ketentuan dan Kebijakan Privasi.</label>
            @error('terms')<p class="err">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn-primary w-full">Daftar</button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-600">Sudah punya akun? <a class="font-medium text-brand-600 hover:underline" href="{{ route('portal.login') }}">Masuk di sini</a></p>
</x-card>
@endsection
