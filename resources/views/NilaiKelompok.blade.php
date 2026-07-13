@extends('layouts.app')

@section('title', 'Penilaian - PjBL')
@section('root_data', '{ sidebarOpen: true, tab: "kelompok" }')

@section('content')
@php
    $projectTitle = $namaProjek ?? ($groupData['project'] ?? 'Projek Tidak Ditemukan');
    $anggotaList = $anggota ?? collect($groupData['members'] ?? [])->pluck('name')->all();
@endphp
<div class="w-full space-y-6">
                <!-- Project Header -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $projectTitle }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            <a href="{{ route('my-project') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-gray-400">Pelaksanaan dan evaluasi</span>
                        </nav>
                    </div>
                    <button class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                </div>

                <div class="flex space-x-12">
                    <!-- Navigasi Ikon Samping (1-5) -->
                    <div class="w-24 space-y-4">
                        @php
                            $menus = [
                                ['icon' => 'fa-comments', 'label' => 'Dekomposisi Masalah', 'r' => 'dekomposisi'],
                                ['icon' => 'fa-tasks', 'label' => 'Penyusunan Rencana', 'r' => 'penyusunan'],
                                ['icon' => 'fa-calendar-alt', 'label' => 'Waktu Progres', 'r' => 'waktu-progres'],
                                ['icon' => 'fa-project-diagram', 'label' => 'Pelaksanaan & Evaluasi', 'r' => 'pelaksanaan'],
                                ['icon' => 'fa-clipboard-check', 'label' => 'Penilaian', 'r' => '#']
                            ];
                        @endphp
                        @foreach($menus as $index => $m)
                            <div class="relative group">
                                <a href="{{ $m['r'] != '#' ? route($m['r'], $id) : '#' }}" 
                                   class="{{ $index == 4 ? 'bg-blue-200 shadow-inner' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                    <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                                </a>
                                <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Form Penilaian -->
                    <div class="flex-1">
                        <!-- Tab Navigation -->
                        <div class="flex space-x-8 border-b border-gray-200 mb-8 text-sm font-bold">
                            <button @click="tab = 'kelompok'" :class="tab == 'kelompok' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-400'" class="pb-2 transition-all">Penilaian Kelompok</button>
                            <a href="{{ route('penilaian-dosen-status', $id) }}" class="text-gray-400 pb-2 hover:text-blue-600 transition-all">Penilaian Dosen</a>
                        </div>

                        @include('partials.flash-messages')

                        <!-- Card Container -->
                        <div class="bg-white rounded-[2.5rem] p-12 shadow-sm border border-gray-100 w-full">
                            @if($existing)
                            <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                                Anda sudah mengisi penilaian ini. Mengubah nilai di bawah akan memperbarui jawaban sebelumnya.
                            </div>
                            @endif

                            <form action="{{ route('penilaian-kelompok.store', $id) }}" method="POST" class="space-y-10">
                                @csrf

                                <!-- Bagian 1: Penilaian Kelompok -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Penilaian Kelompok</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <input type="number" min="0" max="100" name="group_score" required
                                           value="{{ old('group_score', $existing->group_score ?? '') }}"
                                           class="border border-gray-400 rounded-lg p-2 w-20 text-center outline-none focus:border-blue-400 shadow-inner">
                                    @error('group_score')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>

                                <!-- Bagian 2: Penilaian Individu -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Penilaian Individu</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <div class="max-w-md border border-gray-400 rounded-lg overflow-hidden">
                                        <table class="w-full text-xs">
                                            <tr class="bg-white border-b border-gray-400">
                                                <th class="p-3 text-left font-bold border-r border-gray-400 uppercase w-2/3">Nama</th>
                                                <th class="p-3 text-left font-bold uppercase">Nilai</th>
                                            </tr>
                                            @forelse($memberList as $member)
                                            <tr class="border-b border-gray-400 last:border-b-0">
                                                <td class="p-3 border-r border-gray-400 font-medium">{{ $member->full_name }}</td>
                                                <td class="p-1">
                                                    <input type="number" min="0" max="100" required
                                                           name="members[{{ $member->id }}]"
                                                           value="{{ old('members.'.$member->id, $existing->member_scores[$member->id] ?? '') }}"
                                                           class="w-full p-2 outline-none text-center bg-transparent">
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="2" class="p-4 text-center text-slate-400 text-xs italic">Belum ada anggota tim.</td>
                                            </tr>
                                            @endforelse
                                        </table>
                                    </div>
                                    @error('members')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>

                                <!-- Bagian 3: Refleksi -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Refleksi</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <textarea name="reflection" maxlength="500" placeholder="Ketikkkan refleksi anda disini..."
                                              class="w-full border border-gray-400 rounded-2xl p-4 h-28 resize-none outline-none focus:border-blue-400 transition text-xs italic shadow-inner">{{ old('reflection', $existing->reflection ?? '') }}</textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end space-x-4 pt-4">
                                    <a href="{{ route('pelaksanaan', $id) }}" class="bg-gray-300 px-8 py-2 rounded-full text-xs font-bold text-gray-700 hover:bg-gray-400 transition">Batal</a>
                                    <button type="submit" class="bg-blue-600 px-8 py-2 rounded-full text-xs font-bold text-white hover:bg-blue-700 transition shadow-lg shadow-blue-200">Submit</button>
                                </div>
                            </form>
                        </div>

                        <!-- Rekap penilaian tim -->
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 w-full mt-6">
                            <h3 class="text-sm font-bold text-gray-800 mb-4">Rekap Penilaian Tim</h3>
                            @if($peer['submitted'] === 0)
                                <p class="text-xs text-slate-400 italic">Belum ada anggota yang mengisi penilaian.</p>
                            @else
                                <p class="text-xs text-slate-500 mb-4">{{ $peer['submitted'] }} dari {{ $peer['total'] }} anggota sudah mengisi &middot; rata-rata nilai kelompok <strong>{{ $peer['group_average'] }}</strong></p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach($peer['members'] as $member)
                                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <span class="text-xs font-semibold text-slate-700">{{ $member['name'] }}</span>
                                        <span class="text-sm font-bold text-blue-700">{{ $member['average'] ?? '-' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
 </div>
@endsection