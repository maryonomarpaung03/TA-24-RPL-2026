@extends('layouts.app')

@section('title', 'Dashboard - DELPRO')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>.chart-container { position: relative; height: 300px; width: 100%; } [x-cloak] { display: none !important; }</style>
@endpush

@section('content')
<div class="flex-1 p-6 overflow-y-auto">

            @if(!empty($selected_project))
            <div class="space-y-6" x-data="{
                editMode: true,
                problemStatement: '',
                contextBg: '',
                observations: ['Inefficiency in current data processing workflows.', 'High cognitive load for first-year researchers.']
            }">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Problem Identification</p>
                            <h2 class="text-3xl font-bold text-slate-900">{{ $selected_project['name'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $selected_project['description'] }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="inline-flex rounded-full bg-slate-100 p-1 border border-slate-200">
                                <button type="button" @click="editMode = false" :class="editMode ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-blue-700 shadow-sm'" class="rounded-full px-4 py-2 text-xs font-bold transition">Tampilan</button>
                                <button type="button" @click="editMode = true" :class="editMode ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded-full px-4 py-2 text-xs font-bold transition">Edit</button>
                            </div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                                <i class="fas fa-search"></i>
                                Identifikasi Masalah
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[1.9fr_1fr] gap-6">
                    <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-3">Problem Statement</h3>
                                <p class="text-base text-slate-800">What is the central issue you aim to solve?</p>
                                <div class="mt-5" x-show="editMode" x-cloak>
                                    <textarea x-model="problemStatement" rows="4" class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Tuliskan pernyataan masalah inti proyek Anda..."></textarea>
                                </div>
                                <div class="mt-5 rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-800 min-h-[6rem]" x-show="!editMode" x-cloak>
                                    <p class="whitespace-pre-wrap" x-text="problemStatement.trim() ? problemStatement : 'Belum ada pernyataan masalah.'"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                                    <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-3">Context & Background</h3>
                                    <textarea x-show="editMode" x-cloak x-model="contextBg" rows="5" class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Jelaskan lingkungan, pemangku kepentingan, dan mengapa masalah ini penting..."></textarea>
                                    <div class="rounded-3xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-800 min-h-[7.5rem]" x-show="!editMode" x-cloak>
                                        <p class="whitespace-pre-wrap" x-text="contextBg.trim() ? contextBg : 'Belum ada konteks & latar belakang.'"></p>
                                    </div>
                                </div>
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                                    <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-3">Initial Observations</h3>
                                    <div class="space-y-3 text-sm text-slate-700">
                                        <template x-for="(obs, idx) in observations" :key="idx">
                                            <div class="rounded-3xl bg-white border border-slate-200 p-4">
                                                <input type="text" x-show="editMode" x-model="observations[idx]" class="w-full bg-transparent outline-none text-slate-800">
                                                <span x-show="!editMode" x-text="obs"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" x-show="editMode" @click="observations.push('')" class="mt-5 inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition"><i class="fas fa-plus"></i> Add observation</button>
                                </div>
                            </div>

                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-gray-400 font-semibold mb-3">Upload Supporting Evidence</h3>
                                <div x-show="editMode" x-cloak class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">
                                    <p class="mb-4">Drag and drop research papers, data charts, or stakeholder interview transcripts.</p>
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                        <button type="button" class="rounded-full border border-slate-300 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">Browse Files</button>
                                        <button type="button" class="rounded-full border border-blue-600 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">Import from Drive</button>
                                    </div>
                                </div>
                                <div x-show="!editMode" x-cloak class="rounded-[1.5rem] border border-slate-200 bg-white px-5 py-6 text-sm text-slate-600">
                                    <p class="font-semibold text-slate-800 mb-1">Lampiran</p>
                                    <p>Mode tampilan: unggah dokumen dinonaktifkan. Aktifkan mode <span class="font-semibold text-blue-700">Edit</span> untuk menambah bukti pendukung.</p>
                                </div>
                            </div>

                            <div x-show="editMode" x-cloak class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <button type="button" class="rounded-full border border-slate-300 bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">Save Draft</button>
                                <button type="button" class="rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Submit Phase 1</button>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Project Team</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 rounded-3xl bg-slate-50 p-4">
                                    <div class="h-11 w-11 rounded-full bg-blue-600 text-white grid place-items-center font-bold">AS</div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Anisa Safri</p>
                                        <p class="text-xs text-slate-500">Team Lead</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 rounded-3xl bg-slate-50 p-4">
                                    <div class="h-11 w-11 rounded-full bg-slate-200 text-slate-700 grid place-items-center font-bold">BK</div>
                                    <div>
                                        <p class="font-semibold text-slate-900">Budi Kusuma</p>
                                        <p class="text-xs text-slate-500">Data Analyst</p>
                                    </div>
                                </div>
                            </div>
                            <button type="button" x-show="editMode" class="mt-6 w-full rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">Manage Team</button>
                        </div>

                        <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Identification Guide</h3>
                            <div class="space-y-4 text-sm text-slate-700">
                                <div class="rounded-3xl bg-slate-50 p-4">
                                    <p class="font-semibold text-slate-900">Define the core challenge</p>
                                    <p class="mt-2 text-slate-600">Identify clear problem statements, understand the context, and gather evidence.</p>
                                </div>
                                <div class="rounded-3xl bg-slate-50 p-4">
                                    <p class="font-semibold text-slate-900">Break down problems</p>
                                    <p class="mt-2 text-slate-600">Use decomposition to make large problems manageable.</p>
                                </div>
                            </div>
                            <button type="button" class="mt-6 w-full rounded-full border border-blue-600 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">View CT Framework</button>
                        </div>

                        <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                            <h3 class="text-xs uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Phase Progress</h3>
                            <p class="text-sm font-semibold text-slate-900 mb-3">Draft Problem Statement</p>
                            <div class="h-3 rounded-full bg-slate-100 overflow-hidden mb-4"><div class="h-full w-4/5 bg-blue-600"></div></div>
                            <div class="space-y-3 text-sm text-slate-600">
                                <p>Draft Problem Statement</p>
                                <p>Define Stakeholders</p>
                                <p>Upload Data Evidence</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @foreach ($statistics as $key => $value)
                <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500 text-center">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase mb-2">{{ str_replace('_', ' ', $key) }}</h3>
                    <p class="text-3xl font-extrabold">{{ $value }}</p>
                </div>
                @endforeach
            </div>

            <!-- CHARTS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-4 rounded shadow"><h3 class="text-xs font-bold text-gray-400 uppercase mb-4 text-center">Status Projek Terdistribusi</h3><div class="chart-container"><canvas id="pieChart"></canvas></div></div>
                <div class="bg-white p-4 rounded shadow"><h3 class="text-xs font-bold text-gray-400 uppercase mb-4 text-center">Ulasan Progres Projek</h3><div class="chart-container"><canvas id="barChart"></canvas></div></div>
            </div>

            <!-- BOTTOM -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded shadow">
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Projek Berlangsung</h3><a href="{{ route('projek-saya') }}" class="text-blue-500 text-xs font-bold hover:underline">Lihat semua →</a></div>
                    @foreach ($ongoing_projects as $p)<div class="mb-5"><h4 class="text-sm font-bold">{{ $p['name'] }}</h4><div class="flex justify-between text-[10px] mt-1"><span class="text-gray-400">Deadline: {{ $p['deadline'] }}</span><span class="text-blue-600 font-bold">{{ $p['progress'] }}%</span></div><div class="bg-gray-100 h-2 rounded-full mt-1"><div class="bg-blue-600 h-2 rounded-full" style="width: {{ $p['progress'] }}%"></div></div></div>@endforeach
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Deadline Tugas (7 Hari)</h3><a href="#" class="text-blue-500 text-xs font-bold">Lihat semua →</a></div>
                    @foreach ($deadlines as $d)<div class="flex justify-between items-center mb-4 p-2 hover:bg-gray-50 rounded"><div class="flex items-center space-x-3"><div class="w-2 h-2 rounded-full {{ $d['priority'] == 'red' ? 'bg-red-500' : 'bg-yellow-400' }}"></div><div><h4 class="text-sm font-bold">{{ $d['task'] }}</h4><p class="text-[10px] text-gray-400">{{ $d['project'] }}</p></div></div><span class="text-sm font-black">{{ $d['days_left'] }}d</span></div>@endforeach
                </div>
            </div>
            @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } };
    const pieEl = document.getElementById('pieChart');
    const barEl = document.getElementById('barChart');
    if (pieEl) {
        new Chart(pieEl, { type: 'pie', data: { labels: ['Ongoing', 'Planning', 'Completed'], datasets: [{ data: @json(array_values($pie_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderWidth: 0 }] }, options: chartOpt });
    }
    if (barEl) {
        new Chart(barEl, { type: 'bar', data: { labels: ['To Do', 'In Progress', 'Done'], datasets: [{ label: 'Tasks', data: @json(array_values($bar_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderRadius: 4 }] }, options: chartOpt });
    }
})();
</script>
@endpush