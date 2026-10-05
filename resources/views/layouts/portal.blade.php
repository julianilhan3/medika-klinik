@extends('layouts.base')
@section('body')
<div class="min-h-screen bg-sky-50 text-slate-900">
@php
    $nav = [
        ['portal.home',          'Beranda',       ['portal.home']],
        ['portal.doctors',       'Jadwal Dokter', ['portal.doctors', 'booking', 'booking.*']],
        ['tickets.index',        'Tiket Antrean', ['tickets.*']],
        ['portal.records',       'Riwayat Medis', ['portal.records', 'portal.records.*']],
        ['portal.prescriptions', 'Status Obat',   ['portal.prescriptions', 'portal.prescriptions.*']],
    ];
    $name = session('patient.name', 'Pasien');
    $initials = collect(explode(' ', $name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

<header x-data="{ menu: false }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4">
      <a href="{{ route('portal.home') }}" class="flex items-center">
    <img src="{{ asset('images/logo.png') }}" alt="Logo Medika Klinik" class="h-8 w-auto object-contain">
</a>

        {{-- Menu desktop --}}
        <nav class="hidden items-center gap-1 text-sm lg:flex">
            @foreach ($nav as [$route, $label, $patterns])
                <a href="{{ route($route) }}"
                   class="rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs(...$patterns) ? 'bg-brand-50 text-brand-600' : 'text-slate-600 hover:bg-slate-50 hover:text-brand-600' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-1">
            {{-- Menu pengguna --}}
            <details class="group relative" x-data @click.outside="$el.removeAttribute('open')">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                    <span class="grid size-8 place-items-center rounded-full bg-brand-50 text-xs font-semibold text-brand-600">{{ $initials }}</span>
                    <span class="hidden font-medium sm:block">{{ $name }}</span>
                    <x-icon name="lucide:chevron-down" class="transition group-open:rotate-180" />
                </summary>
                <div class="absolute right-0 z-40 mt-2 w-52 rounded-xl bg-white p-1 text-sm shadow-lg ring-1 ring-slate-200">
                    <a href="{{ route('booking') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"><x-icon name="lucide:calendar-plus" /> Booking Online</a>
                    <a href="{{ route('portal.profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"><x-icon name="lucide:user-round" /> Profil Saya</a>
                    <a href="{{ route('portal.password') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"><x-icon name="lucide:shield-check" /> Keamanan akun</a>
                    <div class="my-1 border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('portal.logout') }}">@csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50"><x-icon name="lucide:log-out" /> Keluar</button>
                    </form>
                </div>
            </details>

            {{-- Tombol menu mobile --}}
            <button type="button" @click="menu = !menu" class="grid size-10 place-items-center rounded-lg hover:bg-slate-50 lg:hidden" aria-label="Menu" :aria-expanded="menu">
                <x-icon name="lucide:menu" class="text-2xl" x-show="!menu" />
                <x-icon name="lucide:x" class="text-2xl" x-show="menu" x-cloak />
            </button>
        </div>
    </div>

    {{-- Menu mobile --}}
    <nav x-show="menu" x-cloak
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-slate-100 px-4 py-2 lg:hidden">
        @foreach ($nav as [$route, $label, $patterns])
            <a href="{{ route($route) }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs(...$patterns) ? 'bg-brand-50 text-brand-600' : 'text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </nav>
</header>

<main class="mx-auto max-w-6xl px-4 py-6">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 pb-8 text-xs text-slate-500">
    <span>&copy; {{ date('Y') }} Medika Klinik</span>
    <span class="inline-flex items-center gap-1.5"><x-icon name="lucide:headset" /> Butuh bantuan? Hubungi klinik</span>
</footer>
</div>
@endsection