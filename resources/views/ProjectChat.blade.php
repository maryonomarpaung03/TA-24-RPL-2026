@extends('layouts.app')

@section('title', 'Chat Project - DELPRO')
@section('root_data', '{ sidebarOpen: true }')

@section('content')
<div class="w-full space-y-4">

    {{-- Header --}}
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Project / Chat Room</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
        <p class="mt-1 text-sm text-slate-500">Chat ini hanya untuk anggota project yang tergabung.</p>
    </div>

    @include('partials.flash-messages')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px]">
        {{-- Chat --}}
        <div class="bg-white border border-slate-200 shadow-sm p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Chat Room Project</h3>
                <span class="text-[11px] text-slate-400">Semua anggota project yang tergabung bisa melihat pesan ini.</span>
            </div>

            <div class="h-[500px] overflow-y-auto pr-2 space-y-3" x-init="$el.scrollTop = $el.scrollHeight">
                @forelse($messages as $message)
                    <div class="group flex {{ $message['mine'] ? 'justify-end' : 'justify-start' }}" x-data="{ editing: false }">
                        <div class="max-w-[85%] flex flex-col {{ $message['mine'] ? 'items-end' : 'items-start' }}">
                            <div class="w-full px-4 py-3 rounded-2xl {{ $message['mine'] ? 'bg-blue-500 text-white rounded-br-sm' : 'bg-blue-50 text-slate-700 rounded-bl-sm' }}">
                                <p class="text-[11px] font-semibold opacity-80">
                                    {{ $message['author'] }}
                                    <span class="ml-1 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide
                                        {{ $message['role'] === 'PM'
                                            ? ($message['mine'] ? 'bg-white/20' : 'bg-indigo-100 text-indigo-700')
                                            : ($message['mine'] ? 'bg-white/20' : 'bg-blue-100 text-blue-600') }}">
                                        {{ $message['role'] }}
                                    </span>
                                </p>

                                {{-- Mode edit --}}
                                @if($message['can_edit'])
                                <form action="{{ route('project-chat.update', [$id, $message['id']]) }}" method="POST"
                                      x-show="editing" x-cloak class="mt-2">
                                    @csrf
                                    @method('PUT')
                                    <textarea name="message" rows="2" maxlength="1000" required
                                              class="w-full resize-none rounded-xl border border-white/40 bg-white/90 text-slate-800 px-3 py-2 text-sm outline-none">{{ $message['text'] }}</textarea>
                                    <div class="mt-1 flex justify-end gap-2">
                                        <button type="button" @click="editing = false"
                                                class="px-2 py-1 text-[11px] font-semibold {{ $message['mine'] ? 'text-blue-100 hover:text-white' : 'text-slate-500 hover:text-slate-700' }}">Batal</button>
                                        <button type="submit"
                                                class="bg-white/90 px-3 py-1 text-[11px] font-bold text-blue-700 hover:bg-white">Simpan</button>
                                    </div>
                                </form>
                                @endif

                                {{-- Mode tampil --}}
                                <div @if($message['can_edit']) x-show="!editing" @endif>
                                    @if(!empty($message['text']))
                                    <p class="text-sm mt-1 leading-relaxed break-words whitespace-pre-wrap">{{ $message['text'] }}</p>
                                    @endif

                                    @if(!empty($message['attachment_url']))
                                        @if($message['is_image'])
                                        <a href="{{ $message['attachment_url'] }}" target="_blank" class="mt-2 block">
                                            <img src="{{ $message['attachment_url'] }}" alt="{{ $message['attachment_name'] }}"
                                                 class="max-h-56 w-auto rounded-xl border {{ $message['mine'] ? 'border-blue-400' : 'border-slate-200' }}">
                                        </a>
                                        @else
                                        <a href="{{ $message['attachment_url'] }}" target="_blank"
                                           class="mt-2 flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold
                                           {{ $message['mine'] ? 'border-blue-400 bg-blue-500/40 text-white hover:bg-blue-500/60' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                            <i class="fas fa-paperclip"></i>
                                            <span class="truncate max-w-[180px]">{{ $message['attachment_name'] }}</span>
                                            <i class="fas fa-download ml-auto"></i>
                                        </a>
                                        @endif
                                    @endif

                                    <p class="text-[10px] mt-1 {{ $message['mine'] ? 'text-blue-100' : 'text-slate-500' }}">
                                        {{ $message['time'] }}@if($message['edited']) · diedit @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Aksi edit / hapus --}}
                            @if($message['can_edit'] || $message['can_delete'])
                            <div class="mt-1 flex gap-3 text-[11px] text-slate-400 transition">
                                @if($message['can_edit'])
                                <button type="button" @click="editing = true" class="hover:text-blue-600 font-semibold">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                @endif
                                @if($message['can_delete'])
                                <form action="{{ route('project-chat.delete', [$id, $message['id']]) }}" method="POST"
                                      onsubmit="return confirm('Anda yakin ingin menghapus pesan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hover:text-red-600 font-semibold">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center">
                        <p class="text-sm text-slate-400">Belum ada percakapan. Mulai diskusi tim!</p>
                    </div>
                @endforelse
            </div>

            <form action="{{ route('project-chat.send', $id) }}" method="POST" enctype="multipart/form-data"
                  class="mt-4"
                  x-data="{ fileName: '' }">
                @csrf

                {{-- Preview nama file terpilih --}}
                <div x-show="fileName" x-cloak
                     class="mb-2 flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    <i class="fas fa-paperclip text-slate-400"></i>
                    <span class="truncate" x-text="fileName"></span>
                    <button type="button" class="ml-auto text-slate-400 hover:text-red-500"
                            @click="$refs.attachment.value = ''; fileName = ''">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="flex items-end gap-2">
                    <input type="file" name="attachment" x-ref="attachment" class="hidden"
                           @change="fileName = $refs.attachment.files.length ? $refs.attachment.files[0].name : ''">

                    <button type="button" title="Lampirkan gambar"
                            @click="$refs.attachment.setAttribute('accept', 'image/*'); $refs.attachment.click()"
                            class="shrink-0 rounded-full border border-slate-200 bg-white h-11 w-11 text-slate-500 hover:bg-slate-100 transition">
                        <i class="fas fa-image"></i>
                    </button>
                    <button type="button" title="Lampirkan file"
                            @click="$refs.attachment.setAttribute('accept', ''); $refs.attachment.click()"
                            class="shrink-0 rounded-full border border-slate-200 bg-white h-11 w-11 text-slate-500 hover:bg-slate-100 transition">
                        <i class="fas fa-paperclip"></i>
                    </button>

                    <textarea
                        name="message"
                        rows="1"
                        placeholder="Tulis pesan ke anggota project... (Enter kirim, Shift+Enter baris baru)"
                        maxlength="1000"
                        x-ref="message"
                        @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 160) + 'px'"
                        @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                        class="w-full resize-none rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 leading-relaxed max-h-40 overflow-y-auto"
                    ></textarea>
                    <button type="submit" title="Kirim" class="shrink-0 rounded-full bg-blue-500 h-11 w-11 text-sm font-semibold text-white hover:bg-blue-600 transition">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Anggota --}}
        <aside class="bg-white border border-slate-200 border-t-0 xl:border-t xl:border-l-0 shadow-sm p-5">
            <h3 class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-3">Anggota Project</h3>
            <ul class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                @foreach($members as $member)
                    <li class="flex items-center gap-2.5 py-2">
                        <div class="h-6 w-6 shrink-0 {{ ($member['role'] ?? '') === 'Project Manager' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center text-[10px] font-bold">
                            {{ $member['initials'] ?? collect(explode(' ', $member['name']))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('') }}
                        </div>
                        <p class="text-sm text-slate-700 truncate">{{ $member['name'] }}</p>
                        <span class="ml-auto shrink-0 text-[10px] font-semibold uppercase tracking-wide {{ ($member['role'] ?? '') === 'Project Manager' ? 'text-indigo-500' : 'text-slate-400' }}">{{ ($member['role'] ?? '') === 'Project Manager' ? 'PM' : 'Anggota' }}</span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</div>
@endsection
