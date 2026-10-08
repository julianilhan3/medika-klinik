@extends('layouts.staff')
@section('title', 'Jadwal Praktik')
@section('crumb', 'Jadwal praktik')
@section('heading', 'Jadwal Praktik')
@section('subheading', 'Jadwal praktik Anda minggu ini. Perubahan jadwal dan cuti dikelola admin klinik.')
@section('content')
@php $total = array_sum(array_column($sessions, 'quota')); $booked = array_sum(array_column($sessions, 'booked')); @endphp
<div class="grid gap-4 sm:grid-cols-3">
    <x-stat label="Jadwal minggu ini" :value="count($sessions).' sesi praktik'" icon="lucide:calendar-days" />
    <x-stat label="Total kuota" :value="$total.' pasien'" icon="lucide:users" tone="blue" />
    <x-stat label="Reservasi terdaftar" :value="$booked.' pasien'" icon="lucide:clipboard-check" tone="green" />
</div>
<x-card class="mt-5">
    <div class="flex items-center gap-2 p-5"><x-icon name="lucide:calendar" class="text-brand-600" /><h2 class="font-semibold">{{ $weekStart->translatedFormat('j') }}-{{ $weekStart->copy()->addDays(6)->translatedFormat('j F Y') }}</h2><span class="ml-auto text-sm text-slate-500">{{ $doctor['name'] }} - {{ $doctor['poli'] }}</span></div>
    @include('partials.week-calendar')
</x-card>
<p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:info" class="text-base" /> Jadwal berulang setiap minggu. Tanggal cuti menutup seluruh slot pada hari tersebut.</p>
@endsection
