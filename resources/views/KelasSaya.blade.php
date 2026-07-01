@extends('layouts.app')

@section('title', 'Kelas Saya - DELPRO')
@section('root_data', '{ sidebarOpen: true }')

@section('content')
<div class="w-full space-y-6">

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Mahasiswa</p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900">Kelas Saya</h2>
            <p class="mt-1 text-sm text-slate-500">Kelas yang Anda ikuti. Klik untuk masuk ke ruang kelas & chat room.</p>
        </div>
        <button type="button"
                @click="$dispatch('open-join-class')"
                class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm">
            <i class="fas fa-plus"></i> Gabung Kelas
        </button>
    </div>

    @include('partials.flash-messages')

    @if($classes->isEmpty())
        <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-12 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <i class="fas fa-user-graduate text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-slate-700">Anda belum bergabung ke kelas mana pun.</p>
            <p class="mt-1 text-xs text-slate-500">Minta kode kelas ke dosen, lalu klik <span class="font-semibold">Join Class</span> di sidebar.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($classes as $enrollment)
            @php
                $class = $enrollment->academicClass;
                $classUnread = ($unreadMap[$class->id] ?? ['chat' => 0, 'projects' => 0, 'total' => 0]);
            @endphp
            <a href="{{ route('classes.show', $class->id) }}"
               class="group bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col transition hover:border-blue-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-slate-900 truncate group-hover:text-blue-700">{{ $class->name }}</h3>
                        <p class="text-sm text-slate-500 truncate">{{ $class->course_name }}</p>
                    </div>
                    @if($classUnread['total'] > 0)
                    <span class="inline-flex min-w-[20px] h-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-bold text-white shrink-0"
                          title="{{ $classUnread['chat'] }} chat & {{ $classUnread['projects'] }} proyek baru">{{ $classUnread['total'] > 99 ? '99+' : $classUnread['total'] }}</span>
                    @else
                    <i class="fas fa-arrow-right text-slate-300 group-hover:text-blue-500 transition"></i>
                    @endif
                </div>

                @if($class->lecturer)
                <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-indigo-600 font-semibold">
                    <i class="fas fa-chalkboard-teacher"></i>
                    {{ trim($class->lecturer->displayName()) ?: $class->lecturer->email }}
                </p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                    @if(!empty($class->academic_year))
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ $class->academic_year }}</span>
                    @endif
                    @if(!empty($class->semester))
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">{{ $class->semester }}</span>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                    <span>Kode: <span class="font-mono font-semibold text-slate-600">{{ $class->join_code }}</span></span>
                    <span class="inline-flex items-center gap-1 text-blue-600 font-semibold"><i class="fas fa-comments"></i> Chat Room</span>
                </div>
            </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
