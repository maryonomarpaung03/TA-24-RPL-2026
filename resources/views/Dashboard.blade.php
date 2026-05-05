<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DELPRO</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.chart-container { position: relative; height: 300px; width: 100%; }</style>
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true }">
    <div class="flex">
        <!-- SIDEBAR -->
        <div :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white shadow-md h-screen transition-all duration-300 flex flex-col sticky top-0">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}">
                    <h1 class="text-xl font-bold text-blue-600">DELPRO</h1>
                    <p x-show="sidebarOpen" class="text-gray-400 text-[10px] uppercase font-bold">Monitoring Project</p>
                </a>
            </div>
            <nav class="flex-1 mt-4">
                <a href="{{ route('dashboard') }}" class="flex items-center p-4 {{ Request::is('dashboard') ? 'bg-blue-100 text-blue-600' : 'text-gray-600' }} space-x-3">
                    <i class="fas fa-th-large w-6 text-center"></i>
                    <span x-show="sidebarOpen" class="font-bold">Dashboard</span>
                </a>
                <a href="{{ route('projek-saya') }}" class="flex items-center p-4 text-gray-600 hover:bg-gray-50 space-x-3">
                    <i class="fas fa-project-diagram w-6 text-center"></i>
                    <span x-show="sidebarOpen">Projek Saya</span>
                </a>
            </nav>
            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t text-gray-400 hover:text-blue-600 flex items-center justify-center space-x-2">
                <span x-show="sidebarOpen" class="text-sm">Collapse</span>
                <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i>
            </button>
        </div>

        <!-- MAIN CONTENT -->
        <div class="flex-1 p-6 overflow-hidden">
            <!-- HEADER -->
            <div class="flex justify-between items-center bg-white p-4 rounded shadow mb-6">
                <div>
                    <h2 class="text-lg font-bold">Selamat datang, {{ $user['name'] }}</h2>
                    <p class="text-gray-500 text-sm">{{ $user['role'] }}</p>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('notifikasi') }}" class="relative p-2">
                        <i class="fas fa-bell text-2xl text-gray-300"></i>
                        @if($user['notif_count'] > 0)
                        <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white">{{ $user['notif_count'] }}</span>
                        @endif
                    </a>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">{{ $user['initials'] }}</button>
                        <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-2 w-48 bg-white border rounded shadow-xl z-50">
                            <a href="{{ route('profil') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Profil Saya</a>
                            <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Keluar</button></form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATS -->
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
        </div>
    </div>
    <script>
        const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } };
        new Chart(document.getElementById('pieChart'), { type: 'pie', data: { labels: ['Ongoing', 'Planning', 'Completed'], datasets: [{ data: @json(array_values($pie_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderWidth: 0 }] }, options: chartOpt });
        new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: ['To Do', 'In Progress', 'Done'], datasets: [{ label: 'Tasks', data: @json(array_values($bar_chart_data)), backgroundColor: ['#3b82f6', '#facc15', '#22c55e'], borderRadius: 4 }] }, options: chartOpt });
    </script>
</body>
</html>