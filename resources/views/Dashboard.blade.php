@extends('layouts.app')

@section('title', 'Dashboard - DELPRO')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush

@section('content')
<div class="w-full space-y-6">

            @if(!empty($selected_project) && !($selected_project['can_access_pjbl'] ?? false))
            @include('partials.project-pjbl-gate')
            @elseif(!empty($selected_project))
            @include('partials.problem-identification-workspace')
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
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Projek Berlangsung</h3><a href="{{ route('my-project') }}" class="text-blue-500 text-xs font-bold hover:underline">Lihat semua â†’</a></div>
                    @foreach ($ongoing_projects as $p)<div class="mb-5"><h4 class="text-sm font-bold">{{ $p['name'] }}</h4><div class="flex justify-between text-[10px] mt-1"><span class="text-gray-400">Deadline: {{ $p['deadline'] }}</span><span class="text-blue-600 font-bold">{{ $p['progress'] }}%</span></div><div class="bg-gray-100 h-2 rounded-full mt-1"><div class="bg-blue-600 h-2 rounded-full" style="width: {{ $p['progress'] }}%"></div></div></div>@endforeach
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="flex justify-between items-center mb-4 border-b pb-2"><h3 class="font-bold text-gray-700 text-xs uppercase">Deadline Tugas (7 Hari)</h3><a href="#" class="text-blue-500 text-xs font-bold">Lihat semua â†’</a></div>
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
