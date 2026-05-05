<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waktu Progres - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gantt-grid { display: grid; grid-template-columns: repeat({{ $daysInMonth }}, minmax(30px, 1fr)); border-left: 1px solid #e5e7eb; }
        .gantt-col { border-right: 1px solid #e5e7eb; height: 500px; }
        .truncate-custom { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* 8. Vertical Scroll Bar Area */
        .scroll-container { max-height: 600px; overflow-y: auto; overflow-x: auto; }
    </style>
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR UTAMA -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm z-50">
            <div class="p-6 text-center"><a href="{{ route('dashboard') }}"><h1 class="text-2xl font-bold text-blue-600">DELPRO</h1></a></div>
            <nav class="flex-1 px-4 mt-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded-xl text-gray-500 hover:bg-gray-100">
                    <i class="fas fa-th-large w-6 text-center"></i><span x-show="sidebarOpen">Dashboard</span>
                </a>
                <a href="{{ route('projek-saya') }}" class="flex items-center space-x-3 p-3 rounded-xl bg-blue-100 text-blue-700 font-bold">
                    <i class="fas fa-project-diagram w-6 text-center"></i><span x-show="sidebarOpen">Projek Saya</span>
                </a>
            </nav>
            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t text-gray-400 hover:text-blue-600 flex items-center justify-center space-x-2">
                <span x-show="sidebarOpen">Collapse</span><i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i>
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div><p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">selamat datang,</p><h3 class="font-bold text-gray-800">{{ $user['name'] }}</h3></div>
                <div class="flex items-center space-x-6">
                    <div class="relative"><i class="fas fa-bell text-gray-300 text-2xl"></i><span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span></div>
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-6 space-y-6 overflow-y-auto">
                <!-- 2 & 3. Judul & Breadcrumb -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-blue-500">waktu progres</span>
                        </nav>
                    </div>
                    <div class="relative" x-data="{ openGear: false }">
                        <button @click="openGear = !openGear" class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                        <div x-show="openGear" @click.outside="openGear = false" class="absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-xl z-50 overflow-hidden">
                            <a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">Detail Projek</a>
                            <a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">Edit Projek</a>
                            <a href="{{ route('projek-saya') }}" class="block px-4 py-3 text-sm text-red-500 hover:bg-red-50">Keluar</a>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-6">
                    <!-- 5. Sidebar Navigasi Ikon -->
                    <div class="w-24 space-y-4">
                        @php
                            $menus = [
                                ['icon' => 'fa-comments', 'color' => 'bg-white', 'label' => 'Dekomposisi Masalah', 'route' => route('dekomposisi', $id)],
                                ['icon' => 'fa-tasks', 'color' => 'bg-white', 'label' => 'Penyusunan Rencana', 'route' => route('penyusunan', $id)],
                                ['icon' => 'fa-calendar-alt', 'color' => 'bg-blue-200', 'label' => 'Waktu Progres', 'route' => '#'],
                                ['icon' => 'fa-project-diagram', 'color' => 'bg-white', 'label' => 'Pelaksanaan & Evaluasi', 'route' => '#'],
                                ['icon' => 'fa-clipboard-check', 'color' => 'bg-white', 'label' => 'Penilaian', 'route' => '#']
                            ];
                        @endphp
                        @foreach($menus as $menu)
                        <div class="relative group">
                            <a href="{{ $menu['route'] }}" class="{{ $menu['color'] }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                            </a>
                            <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 shadow-lg">
                                {{ $menu['label'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- 6 & 7. GANTT CHART (WAKTU PROGRES) -->
                    <div class="flex-1 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                        <!-- 6. Filter Bulan -->
                        <div class="flex justify-end items-center mb-6 space-x-2">
                            <label class="text-xs font-bold text-gray-500">Bulan :</label>
                            <form action="" method="GET" class="relative" x-data="{ openMonth: false }">
                                <div class="flex items-center bg-gray-50 border rounded-full px-4 py-1.5 cursor-pointer" @click="openMonth = !openMonth">
                                    <input type="text" value="{{ $monthName }}" readonly class="bg-transparent text-xs font-bold outline-none w-24 cursor-pointer">
                                    <i class="fas fa-chevron-down text-[10px] text-gray-400 ml-2"></i>
                                </div>
                                <div x-show="openMonth" @click.outside="openMonth = false" class="absolute right-0 mt-2 w-40 bg-white border rounded-xl shadow-xl z-50 max-h-48 overflow-y-auto">
                                    @foreach($months as $num => $name)
                                    <a href="?month={{ $num }}" class="block px-4 py-2 text-xs hover:bg-blue-50 {{ $selectedMonth == $num ? 'bg-blue-100 font-bold' : '' }}">{{ $name }}</a>
                                    @endforeach
                                </div>
                            </form>
                        </div>

                        <!-- Area Tabel Waktu -->
                        <div class="scroll-container border rounded-xl relative">
                            <!-- 6. Header Tabel (Bulan & Tanggal) -->
                            <div class="bg-gray-400 text-white text-center py-1 text-sm font-bold border-b border-gray-500">
                                {{ $monthName }} 2026
                            </div>
                            <div class="bg-gray-300 grid grid-cols-{{ $daysInMonth }} text-center border-b border-gray-500">
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                <div class="py-1 text-[10px] font-bold border-r border-gray-400 last:border-r-0">{{ $i }}</div>
                                @endfor
                            </div>

                            <!-- 7. Baris Progres (Tugas) -->
                            <div class="relative bg-white min-h-[500px]">
                                <!-- Kolom Vertikal -->
                                <div class="absolute inset-0 gantt-grid pointer-events-none">
                                    @for($i = 1; $i <= $daysInMonth; $i++)
                                    <div class="gantt-col border-r border-gray-100 last:border-r-0"></div>
                                    @endfor
                                </div>

                                <!-- Bar Tugas -->
                                <div class="relative pt-4 space-y-4">
                                    @foreach($tasks as $task)
                                    @php
                                        // Menghitung posisi kolom (grid-column: start / end)
                                        $gridStart = $task['start'];
                                        $gridSpan = ($task['end'] - $task['start']) + 1;
                                    @endphp
                                    <div class="grid grid-cols-{{ $daysInMonth }} h-8">
                                        <div style="grid-column: {{ $gridStart }} / span {{ $gridSpan }};" 
                                             class="{{ $task['color'] }} rounded-lg flex items-center px-3 shadow-md border border-black/10 z-10">
                                            <span class="text-[9px] font-bold text-white truncate-custom">
                                                {{ $task['name'] }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- 6c. Anggota Aktif di Pojok Kanan Atas Tabel -->
                        <div class="flex justify-end -mt-[580px] pr-4 relative z-20">
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-blue-600 shadow-sm">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>