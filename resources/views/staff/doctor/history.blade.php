@extends('layouts.staff')
@section('title', 'Riwayat Pemeriksaan')
@section('crumb', 'Riwayat pemeriksaan')
@section('heading', 'Riwayat Pemeriksaan')
@section('subheading', 'Catatan pemeriksaan yang pernah Anda lakukan.')
@section('content')
<x-card>
    <form class="p-5"><div class="relative"><x-icon name="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input name="q" value="{{ $q }}" class="input pl-10" placeholder="Cari nama pasien atau diagnosis"></div></form>
    <div class="divide-y divide-slate-100">
    @forelse ($rows as $h)
        <div x-data="{ open: false }" class="px-5 py-4">
            <button @click="open = !open" class="flex w-full items-center justify-between gap-3 text-left">
                <div><p class="font-medium text-slate-900">{{ $h['patient'] }}</p><p class="text-sm text-slate-500">{{ $h['diagnosis'] }}</p></div>
                <div class="flex items-center gap-3 text-sm text-slate-500"><span>{{ $h['date'] }}</span><x-icon name="lucide:chevron-down" ::class="open && 'rotate-180'" class="transition" /></div>
            </button>
            <dl x-show="open" x-cloak class="mt-3 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-slate-500">Keluhan</dt><dd>{{ $h['complaint'] }}</dd></div>
                <div><dt class="text-xs text-slate-500">Tekanan darah</dt><dd>{{ $h['bp'] }} mmHg</dd></div>
                <div><dt class="text-xs text-slate-500">Resep</dt><dd>{{ $h['rx'] }}</dd></div>
                <div class="sm:col-span-3"><dt class="text-xs text-slate-500">Catatan</dt><dd>{{ $h['note'] }}</dd></div>
            </dl>
        </div>
    @empty
        <x-empty icon="lucide:search-x" title="Riwayat tidak ditemukan" text="Coba kata kunci lain." />
    @endforelse
    </div>
</x-card>
@endsection
