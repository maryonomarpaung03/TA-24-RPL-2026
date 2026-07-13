@extends('layouts.app')

@section('title', 'Semua Notifikasi - PjBL')

@section('content')
<div class="w-full space-y-6">

    @include('partials.flash-messages')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Notifikasi</h1>
            <p class="text-sm text-slate-500 mt-1">
                @if($unreadCount > 0)
                    Anda memiliki {{ $unreadCount }} notifikasi belum dibaca.
                @else
                    Semua notifikasi sudah dibaca.
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifikasi.read-all') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                <i class="fas fa-check-double text-blue-600"></i>
                Tandai semua dibaca
            </button>
        </form>
        @endif
    </div>

    <div class="flex gap-2">
        <a href="{{ route('notifikasi', ['filter' => 'all']) }}"
           class="rounded-full px-4 py-2 text-xs font-bold transition {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Semua
        </a>
        <a href="{{ route('notifikasi', ['filter' => 'unread']) }}"
           class="rounded-full px-4 py-2 text-xs font-bold transition {{ $filter === 'unread' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Belum dibaca
            @if($unreadCount > 0)
            <span class="ml-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] text-white">{{ $unreadCount }}</span>
            @endif
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
        @forelse($notifications as $note)
            @include('partials.notification-item', ['note' => $note, 'role' => $role])
        @empty
        <div class="px-6 py-16 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i class="fas fa-bell-slash text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">
                {{ $filter === 'unread' ? 'Tidak ada notifikasi belum dibaca' : 'Belum ada notifikasi' }}
            </p>
            <p class="text-xs text-slate-400 mt-2">Aktivitas proyek Anda akan muncul di halaman ini.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
