@extends('layouts.app')

@section('title', 'Dashboard - DELPRO')

@section('content')
<div class="flex-1 p-6">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @foreach ($statistics as $key => $value)
        <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500 text-center">
            <h3 class="text-[10px] font-bold text-gray-400 uppercase mb-2">{{ str_replace('_', ' ', $key) }}</h3>
            <p class="text-3xl font-extrabold">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded shadow">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="font-bold text-gray-700 text-xs uppercase">Approval Project</h3>
                <a href="{{ route('dosen.persetujuan') }}" class="text-blue-500 text-xs font-bold hover:underline">Lihat semua &rarr;</a>
            </div>
            @forelse($pending_approvals as $project)
            <div class="mb-5 last:mb-0">
                <div class="flex justify-between items-start gap-3">
                    <h4 class="text-sm font-bold text-gray-800">{{ $project['name'] }}</h4>
                    <a href="{{ route('dosen.persetujuan.show', $project['id']) }}"
                       class="text-blue-500 text-xs font-bold hover:underline shrink-0">Lihat detail &rarr;</a>
                </div>
                <div class="flex justify-between text-[10px] mt-1">
                    <span class="text-gray-400">{{ $project['creator_name'] }} · {{ $project['course'] }}</span>
                    <span class="text-amber-600 font-bold">Menunggu</span>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Diajukan: {{ $project['submitted_at'] }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-6">Tidak ada proyek yang menunggu persetujuan.</p>
            @endforelse
            @if($pending_total > count($pending_approvals))
            <p class="text-[10px] text-gray-400 text-center mt-2">+{{ $pending_total - count($pending_approvals) }} proyek lainnya di halaman persetujuan.</p>
            @endif
        </div>

        <div class="bg-white p-5 rounded shadow">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="font-bold text-gray-700 text-xs uppercase">Identifikasi Masalah (Voting)</h3>
                <a href="{{ route('notifikasi') }}" class="text-blue-500 text-xs font-bold hover:underline">Lihat semua &rarr;</a>
            </div>
            @forelse($problem_voting_notifications as $note)
            <div class="flex justify-between items-center mb-4 p-2 hover:bg-gray-50 rounded last:mb-0">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></div>
                    <div class="min-w-0">
                        <h4 class="text-sm font-bold text-gray-800 truncate">{{ $note['problem_title'] }}</h4>
                        <p class="text-[10px] text-gray-400">{{ $note['project_name'] }} · {{ $note['student_group'] }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <span class="text-sm font-black text-blue-600">{{ $note['votes'] }}</span>
                    <p class="text-[10px] text-gray-400">suara</p>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-6">Belum ada masalah hasil voting.</p>
            @endforelse
            @if($notifications_total > count($problem_voting_notifications))
            <p class="text-[10px] text-gray-400 text-center mt-2">+{{ $notifications_total - count($problem_voting_notifications) }} notifikasi lainnya.</p>
            @endif
        </div>
    </div>
</div>
@endsection
