<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Dosen - DELPRO</title>
    <!-- Library untuk Styling dan Interaktivitas -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm z-50">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}"><h1 class="text-2xl font-bold text-blue-600">DELPRO</h1></a>
                <p x-show="sidebarOpen" class="text-gray-500 text-[10px] uppercase font-bold tracking-widest mt-1">Monitoring Project</p>
            </div>
            <nav class="flex-1 px-4 mt-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded-xl text-gray-500 hover:bg-gray-100 transition">
                    <i class="fas fa-th-large w-6 text-center"></i><span x-show="sidebarOpen">Dashboard</span>
                </a>
                <a href="{{ route('projek-saya') }}" class="flex items-center space-x-3 p-3 rounded-xl bg-blue-100 text-blue-700 font-bold">
                    <i class="fas fa-project-diagram w-6 text-center"></i><span x-show="sidebarOpen">Projek Saya</span>
                </a>
                <div class="px-3 py-2 text-gray-400 text-xs flex items-center space-x-3">
                    <i class="fas fa-tasks w-6 text-center"></i><span x-show="sidebarOpen">My Task</span>
                </div>
            </nav>
            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t text-gray-400 hover:text-blue-600 flex items-center justify-center space-x-2 transition">
                <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i><span x-show="sidebarOpen" class="text-sm">Collapse</span>
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div>
                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">selamat datang,</p>
                    <h3 class="font-bold text-gray-800 text-sm">{{ $user['name'] }}</h3>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-300 text-2xl"></i>
                        @if($user['notif_count'] > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">
                            {{ $user['notif_count'] }}
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                             <p class="text-[11px] font-bold text-gray-800 leading-none">{{ $user['name'] }}</p>
                             <p class="text-[10px] text-gray-400 font-medium mt-1 leading-none">{{ $user['role'] }}</p>
                        </div>
                        <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm">{{ $user['initials'] }}</div>
                    </div>
                </div>
            </header>

            <div class="p-6 space-y-6">
                <!-- Project Name Header Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            projek saya/ <span class="text-gray-400">Pelaksanaan dan evaluasi</span>
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
                                   class="{{ $index == 4 ? 'bg-blue-200' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                    <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                                </a>
                                <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Main Content Area -->
                    <div class="flex-1">
                        <!-- Tabs -->
                        <div class="flex space-x-8 border-b border-gray-200 mb-10 text-sm font-bold">
                            <a href="{{ route('penilaian-kelompok', $id) }}" class="text-gray-800 pb-2 hover:text-blue-600 transition">Penilaian Kelompok</a>
                            <button class="text-blue-600 border-b-2 border-blue-600 pb-2">Penilaian Dosen</button>
                        </div>
                        
                        <!-- Empty State Message -->
                        <div class="flex flex-col items-start pt-10">
                            <h3 class="text-4xl text-gray-600 font-medium leading-tight">
                                Belum ada penilaian yang dilakukan oleh dosen
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>