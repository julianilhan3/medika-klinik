@extends('layouts.base')
@php

    $isPatient = ! request()->is('staff/*');

    $staff = $isPatient
        ? ['name' => session('patient.name'), 'role' => 'patient']
        : [
            'name' => auth()->user()?->name ?? auth()->user()?->username ?? 'Staff',
            'role' => auth()->user()?->role ?? 'staff',
        ];

    $logoutUrl = $isPatient ? route('portal.logout') : route('staff.logout');

    $passwordUrl = $isPatient ? route('portal.password') : route('staff.password');

    $menuKey = $staff['role'];

    if ($staff['role'] === 'doctor' && request()->routeIs('doctor.exam*', 'doctor.rx*')) {
        $menuKey = 'doctor_exam';
    }

    $menu = config('clinic.menus.'.$menuKey);

    $roleLabel = config('clinic.roles.'.$staff['role'].'.label');

    $initials = collect(explode(' ', preg_replace('/^dr\.\s*/i', '', $staff['name'])))->take(2)->map(fn ($w) => strtoupper($w[0]))->implode('');

@endphp

@section('body')
<div x-data="{ sidebar: false }" class="flex min-h-screen">
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-navy-800 text-slate-200 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex h-16 items-center gap-2.5 px-5">
           <a href="#" class="flex items-center">
    <img src="{{ asset('images/logo-dashboard.png') }}" alt="Logo Medika Klinik" class="h-14 mt-5 w-auto object-contain">
</a> 
        </div>
        <p class="px-5 pb-2 pt-4 text-xs text-slate-400">{{ $isPatient ? 'Menu pasien' : 'Area '.strtolower($roleLabel) }}</p>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3">
            @foreach ($menu as $item)
                <a href="{{ route($item['route'], ($item['params'] ?? false) ? ['no' => request()->route('no')] : []) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs(...(array) $item['match']) ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="m-3 rounded-xl border border-white/10 bg-white/5 p-4 text-xs">
            <x-icon name="lucide:headset" class="text-lg text-slate-300" />
            <p class="mt-2 font-semibold text-white">Perlu bantuan?</p>
            <p class="mt-1 text-slate-400">{{ $isPatient ? 'Hubungi pendaftaran klinik di jam layanan.' : 'Hubungi tim IT klinik untuk bantuan sistem.' }}</p>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200/70 bg-white/80 px-4 backdrop-blur sm:px-8">
            <button @click="sidebar = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu"><x-icon name="lucide:menu" class="text-2xl" /></button>
            <p class="truncate text-sm text-slate-500">{{ $isPatient ? 'Portal pasien' : 'Portal staf' }} <span class="px-1 text-slate-300">/</span> <span class="text-slate-700">@yield('crumb')</span></p>

            <div class="relative ml-auto" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="flex items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-100">
                    <span class="grid size-9 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">{{ $initials }}</span>
                    <span class="hidden text-left leading-tight sm:block"><span class="block text-sm font-semibold">{{ $staff['name'] }}</span><span class="block text-xs text-slate-500">{{ $roleLabel }}</span></span>
                    <x-icon name="lucide:chevron-down" class="text-slate-400" />
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    <a href="{{ $passwordUrl }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="lucide:key-round" /> Ganti Password</a>
                    <form method="POST" action="{{ $logoutUrl }}">@csrf
                        <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm hover:bg-slate-50"><x-icon name="lucide:log-out" /> Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">@yield('heading')</h1>
                    <p class="mt-1 text-sm text-slate-500">@yield('subheading')</p>
                </div>
                @yield('actions')
            </div>
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="flex flex-wrap justify-between gap-2 px-4 py-4 text-xs text-slate-500 sm:px-8">
            <span>&copy; {{ date('Y') }} Medika Klinik &middot; {{ $isPatient ? 'Portal pasien' : 'Portal staf' }}</span>
            <span>Waktu dalam WIB</span>
        </footer>
    </div>
</div>
@endsection
