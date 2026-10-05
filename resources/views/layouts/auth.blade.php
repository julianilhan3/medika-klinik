@extends('layouts.base')
@section('body')
<div class="flex min-h-screen flex-col">
    <header class="flex h-16 items-center justify-between bg-white px-4 sm:px-8">
        <a href="{{ route('portal.home') }}" class="flex items-center">
    <img src="{{ asset('images/logo.png') }}" alt="Logo Medika Klinik" class="h-8 w-auto object-contain">
</a>
        @yield('top-right')
    </header>
    <main class="flex flex-1 items-center justify-center px-4 py-10">@yield('content')</main>
    <footer class="px-4 py-4 text-center text-xs text-slate-500">&copy; {{ date('Y') }} Medika Klinik</footer>
</div>
@endsection
