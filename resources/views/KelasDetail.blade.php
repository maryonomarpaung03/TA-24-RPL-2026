@extends('layouts.app')

@section('title', $academicClass->name . ' - DELPRO')
@section('root_data', '{ sidebarOpen: true }')

@section('content')
<div class="w-full space-y-4"
     x-data="{ tab: 'chat' }">

    {{-- Header kelas --}}
    <div class="bg-white p-6 shadow-sm border border-gray-200">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Ruang Kelas</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-900">{{ $academicClass->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $academicClass->course_name }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px]">
                    @if($academicClass->lecturer)
                    <span class="inline-flex items-center gap-1 bg-indigo-50 px-3 py-1 font-semibold text-indigo-700">
                        <i class="fas fa-chalkboard-teacher"></i> {{ trim($academicClass->lecturer->displayName()) ?: $academicClass->lecturer->email }}
                    </span>
                    @endif
                    @if(!empty($academicClass->academic_year))
                    <span class="inline-flex items-center gap-1 bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                        <i class="fas fa-calendar"></i> {{ $academicClass->academic_year }}
                    </span>
                    @endif
                    @if(!empty($academicClass->semester))
                    <span class="inline-flex items-center gap-1 bg-slate-100 px-3 py-1 font-semibold text-slate-600">
                        Semester {{ $academicClass->semester }}
                    </span>
                    @endif
                    <span class="inline-flex items-center gap-1 bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">
                        <i class="fas fa-users"></i> {{ $studentCount }} mahasiswa
                    </span>
                </div>
            </div>

            <div class="border border-slate-200 bg-slate-50 px-4 py-3 text-center">
                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-semibold">Kode Kelas</p>
                <p class="mt-1 font-mono text-lg font-bold tracking-widest text-slate-900">{{ $academicClass->join_code }}</p>
            </div>
        </div>

        {{-- Tab menu --}}
        <div class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4">
            <button type="button" @click="tab = 'chat'"
                    :class="tab === 'chat' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    class="relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-comments"></i> Chat Room
                @if(($unread['chat'] ?? 0) > 0)
                <span class="inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unread['chat'] }}</span>
                @endif
            </button>
            <button type="button" @click="tab = 'members'"
                    :class="tab === 'members' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-user-friends"></i> Anggota
            </button>
            <button type="button" @click="tab = 'projects'"
                    :class="tab === 'projects' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    class="relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition">
                <i class="fas fa-project-diagram"></i> Proyek
                @if(($unread['projects'] ?? 0) > 0)
                <span class="inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unread['projects'] }}</span>
                @endif
            </button>
        </div>
    </div>

    @include('partials.flash-messages')

    {{-- Chat Room --}}
    <div x-show="tab === 'chat'" class="grid grid-cols-1 xl:grid-cols-[1fr_300px]">
        <div class="bg-white border border-slate-200 shadow-sm p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Chat Room Kelas</h3>
                <span class="text-[11px] text-slate-400">Semua dosen &amp; mahasiswa yang tergabung bisa melihat pesan ini.</span>
            </div>

            <div class="h-[500px] overflow-y-auto pr-2 space-y-3" x-init="$el.scrollTop = $el.scrollHeight">
                @forelse($messages as $message)
                    <div class="group flex {{ $message['mine'] ? 'justify-end' : 'justify-start' }}" x-data="{ editing: false }">
                        <div class="max-w-[85%] flex flex-col {{ $message['mine'] ? 'items-end' : 'items-start' }}">
                            <div class="w-full px-4 py-3 rounded-2xl {{ $message['mine'] ? 'bg-blue-500 text-white rounded-br-sm' : 'bg-blue-50 text-slate-700 rounded-bl-sm' }}">
                                <p class="text-[11px] font-semibold opacity-80">
                                    {{ $message['author'] }}
                                    <span class="ml-1 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide
                                        {{ $message['role'] === 'Dosen'
                                            ? ($message['mine'] ? 'bg-white/20' : 'bg-indigo-100 text-indigo-700')
                                            : ($message['mine'] ? 'bg-white/20' : 'bg-blue-100 text-blue-600') }}">
                                        {{ $message['role'] }}
                                    </span>
                                </p>

                                {{-- Mode edit --}}
                                @if($message['can_edit'])
                                <form action="{{ route('classes.chat.update', [$academicClass->id, $message['id']]) }}" method="POST"
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
                                <form action="{{ route('classes.chat.delete', [$academicClass->id, $message['id']]) }}" method="POST"
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
                        <p class="text-sm text-slate-400">Belum ada percakapan. Mulai diskusi kelas!</p>
                    </div>
                @endforelse
            </div>

            <form action="{{ route('classes.chat.send', $academicClass->id) }}" method="POST" enctype="multipart/form-data"
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
                        placeholder="Tulis pesan ke kelas... (Enter kirim, Shift+Enter baris baru)"
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

                @error('attachment')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </form>
        </div>

        <aside class="bg-white border border-slate-200 border-t-0 xl:border-t xl:border-l-0 shadow-sm p-5">
            <h3 class="text-xs uppercase tracking-[0.2em] text-slate-400 font-semibold mb-3">Anggota Kelas</h3>
            <ul class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                @foreach($participants as $member)
                    <li class="flex items-center gap-2.5 py-2">
                        <div class="h-6 w-6 shrink-0 {{ $member['role'] === 'Dosen' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center text-[10px] font-bold">
                            {{ collect(explode(' ', $member['name']))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('') }}
                        </div>
                        <p class="text-sm text-slate-700 truncate">{{ $member['name'] }}</p>
                        <span class="ml-auto shrink-0 text-[10px] font-semibold uppercase tracking-wide {{ $member['role'] === 'Dosen' ? 'text-indigo-500' : 'text-slate-400' }}">{{ $member['role'] }}</span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>

    {{-- Daftar Anggota (tab) --}}
    <div x-show="tab === 'members'" x-cloak class="bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Daftar Anggota ({{ $participants->count() }})</h3>

        @if($canManage)
        {{-- Form tambah anggota (khusus dosen) --}}
        <form action="{{ route('classes.members.add', $academicClass->id) }}" method="POST"
              class="mb-4 border border-slate-200 bg-slate-50 p-3">
            @csrf
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tambah Mahasiswa</label>
            <div class="flex gap-2">
                <input type="text" name="identifier" required
                       placeholder="Email atau NIM mahasiswa"
                       class="w-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                <button type="submit" class="shrink-0 bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <i class="fas fa-user-plus"></i> Tambah
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">Mahasiswa harus sudah punya akun DELPRO.</p>
        </form>
        @endif

        <ul class="divide-y divide-slate-100">
            @foreach($participants as $member)
                <li class="flex items-center gap-3 py-2.5">
                    <div class="h-7 w-7 shrink-0 {{ $member['role'] === 'Dosen' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center text-[11px] font-bold">
                        {{ collect(explode(' ', $member['name']))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('') }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-700 truncate">{{ $member['name'] }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $member['email'] }}</p>
                    </div>
                    <span class="ml-auto shrink-0 text-[11px] font-semibold uppercase tracking-wide {{ $member['role'] === 'Dosen' ? 'text-indigo-500' : 'text-slate-400' }}">{{ $member['role'] }}</span>
                    @if($member['removable'])
                    <form action="{{ route('classes.members.remove', [$academicClass->id, $member['id']]) }}" method="POST"
                          onsubmit="return confirm('Keluarkan {{ $member['name'] }} dari kelas?');" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Keluarkan anggota"
                                class="border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Proyek Kelas (tab) --}}
    <div x-show="tab === 'projects'" x-cloak class="bg-white border border-slate-200 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Proyek Kelas ({{ $projects->count() }})</h3>
                <p class="text-[11px] text-slate-500">Proyek yang dibuat di dalam kelas ini.</p>
            </div>
            <a href="{{ route('buat-projek', ['class' => $academicClass->id]) }}"
               class="inline-flex items-center gap-2 bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i> Buat Proyek
            </a>
        </div>

        @if($projects->isEmpty())
            <div class="border border-dashed border-slate-200 p-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <p class="text-sm font-semibold text-slate-700">Belum ada proyek di kelas ini.</p>
                <p class="mt-1 text-xs text-slate-500">Klik <span class="font-semibold">Buat Proyek</span> untuk memulai.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($projects as $project)
                <a href="{{ $project['url'] }}"
                   class="group border border-slate-200 p-4 flex flex-col transition hover:border-blue-300 hover:shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="text-sm font-bold text-slate-900 truncate group-hover:text-blue-700">{{ $project['name'] }}</h4>
                        <span class="shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                            @switch($project['db_status'])
                                @case('active') bg-green-100 text-green-700 @break
                                @case('completed') bg-blue-100 text-blue-700 @break
                                @case('pending_approval')
                                @case('pending_revision') bg-amber-100 text-amber-700 @break
                                @case('rejected') bg-red-100 text-red-700 @break
                                @default bg-slate-100 text-slate-600
                            @endswitch">
                            {{ $project['status'] }}
                        </span>
                    </div>
                    @if(!empty($project['group_name']))
                    <p class="mt-1 text-[11px] text-slate-500 truncate"><i class="fas fa-users mr-1"></i>{{ $project['group_name'] }}</p>
                    @endif
                    @if(!empty($project['description']))
                    <p class="mt-2 text-xs text-slate-600 line-clamp-2">{{ $project['description'] }}</p>
                    @endif
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                        <span>{{ $project['created_at'] }}</span>
                        <span class="inline-flex items-center gap-1 text-blue-600 font-semibold">Buka <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
