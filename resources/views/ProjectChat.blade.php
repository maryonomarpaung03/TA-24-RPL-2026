@extends('layouts.app')

@section('title', 'Chat Project - DELPRO')
@section('root_data', '{ sidebarOpen: true }')

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Project / Chat Room</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
        <p class="mt-2 text-xs text-slate-500">Chat ini hanya untuk anggota project yang tergabung.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
            <div class="h-[520px] overflow-y-auto pr-2 space-y-3">
                @forelse($messages as $message)
                    @php $mine = ($message['author'] ?? '') === ($user['name'] ?? ''); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $mine ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                            <p class="text-[11px] font-semibold opacity-80">{{ $message['author'] ?? '-' }}</p>
                            <p class="text-sm mt-1 leading-relaxed">{{ $message['text'] ?? '' }}</p>
                            <p class="text-[10px] mt-1 {{ $mine ? 'text-blue-100' : 'text-slate-500' }}">{{ $message['time'] ?? '' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada percakapan.</p>
                @endforelse
            </div>

            <form action="{{ route('project-chat.send', $id) }}" method="POST" class="mt-4 flex items-center gap-3">
                @csrf
                <input
                    type="text"
                    name="message"
                    placeholder="Tulis pesan ke anggota project..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    required
                >
                <button type="submit" class="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Kirim</button>
            </form>
        </div>

        <aside class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-4">Anggota Project</h3>
            <div class="space-y-3">
                @foreach($members as $member)
                    <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-3 py-3">
                        <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                            {{ collect(explode(' ', $member))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('') }}
                        </div>
                        <p class="text-sm font-semibold text-slate-700">{{ $member }}</p>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>
@endsection
