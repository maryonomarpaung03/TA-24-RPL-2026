@php
    $enrolledClasses = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('class_members')) {
        $enrolledClasses = \App\Models\ClassMember::query()
            ->with('academicClass')
            ->where('user_id', auth()->id())
            ->latest('joined_at')
            ->get();
    }
@endphp

<div x-data="{ joinOpen: @js(session('open_join_class') || $errors->has('join_code')) }"
     @keydown.escape.window="joinOpen = false">

    <button type="button"
            @click="joinOpen = true"
            class="flex w-full items-center gap-3 p-3 rounded-xl transition bg-blue-600 text-white hover:bg-blue-700 shadow-sm"
            title="Join class">
        <i class="fas fa-plus w-6 text-center text-lg"></i>
        <span x-show="sidebarOpen" class="font-semibold">Join Class</span>
    </button>

    <template x-teleport="body">
        <div x-show="joinOpen"
             x-cloak
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60"
             role="dialog"
             aria-modal="true"
             aria-labelledby="join-class-title"
             @click.self="joinOpen = false">
            <div @click.stop
                 class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.2em] text-blue-600 font-bold">Student</p>
                        <h3 id="join-class-title" class="font-bold text-slate-900 text-lg">Join Class</h3>
                    </div>
                    <button type="button" @click="joinOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if(session('success') && !session('class_created'))
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('info'))
                <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                    {{ session('info') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    {{ session('error') }}
                </div>
                @endif

                @if(Route::has('classes.join'))

<form
    method="POST"
    action="{{ route('classes.join') }}"
    class="space-y-4"
>
    @csrf

@else

<div class="rounded-xl bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-700">
    Fitur join class sedang dinonaktifkan sementara.
</div>

@endif
            </div>
        </div>
    </template>
    
    @if($enrolledClasses->isNotEmpty())
    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-3" x-show="sidebarOpen">
        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-semibold mb-2">My classes</p>
        <ul class="space-y-2 max-h-32 overflow-y-auto">
            @foreach($enrolledClasses as $enrollment)
            <li class="rounded-xl bg-white px-3 py-2 border border-slate-100">
                <p class="text-xs font-semibold text-slate-800 truncate">{{ $enrollment->academicClass->name }}</p>
                <p class="text-[10px] text-slate-500 truncate">{{ $enrollment->academicClass->course_name }}</p>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
