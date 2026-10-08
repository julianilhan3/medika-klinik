@extends('layouts.staff')
@section('title', 'Antrean Pasien')
@section('crumb', 'Antrean pasien')
@section('heading', 'Antrean Pasien')
@section('subheading', 'Kelola antrean hari ini dan panggil pasien untuk pemeriksaan.')
@section('actions')<span class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><x-icon name="lucide:calendar" /> {{ now()->translatedFormat('l, j F Y') }}</span>@endsection
@section('content')
@include('staff.doctor._queue-body')
@endsection
