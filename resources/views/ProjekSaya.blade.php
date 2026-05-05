<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projek Saya - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm">
            <div class="p-6 text-center"><a href="{{ route('dashboard') }}"><h1 class="text-2xl font-bold text-blue-600">DELPRO</h1><p x-show="sidebarOpen" class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Monitoring Project</p></a></div>
            <nav class="flex-1 px-4 mt-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded-xl text-gray-500 hover:bg-gray-100"><i class="fas fa-th-large w-6 text-center"></i><span x-show="sidebarOpen">Dashboard</span></a>
                <a href="{{ route('projek-saya') }}" class="flex items-center space-x-3 p-3 rounded-xl bg-blue-100 text-blue-700 font-bold"><i class="fas fa-project-diagram w-6 text-center"></i><span x-show="sidebarOpen">Projek Saya</span></a>
            </nav>
            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t text-gray-400 hover:text-blue-600 flex items-center justify-center space-x-2"><span x-show="sidebarOpen" class="text-sm">Collapse</span><i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i></button>
        </aside>

        <!-- MAIN -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div><p class="text-gray-400 text-xs">selamat datang,</p><h3 class="font-bold text-gray-800">{{ $user['name'] }}</h3></div>
                <div class="flex items-center space-x-6"><a href="{{ route('notifikasi') }}" class="relative p-2"><i class="fas fa-bell text-gray-300 text-2xl"></i><span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span></a><div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm">{{ $user['initials'] }}</div></div>
            </header>

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <div><h2 class="text-3xl font-bold text-gray-900">Projek Saya</h2><p class="text-gray-500">Kelola semua proyek akademik Anda</p></div>
                    <a href="{{ route('buat-projek') }}" class="bg-black text-white px-6 py-2.5 rounded-full font-bold hover:bg-gray-800 transition">+ Buat Projek</a>
                </div>

                <!-- SEARCH & STATUS -->
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4 mb-8">
                    <div class="flex-1 relative" x-data="{ openHistory: false }">
                        <div class="flex items-center bg-gray-50 rounded-full px-6 py-2 border border-transparent focus-within:border-blue-300 transition"><i class="fas fa-search text-gray-400 mr-3"></i><input @focus="openHistory = true" @click.outside="openHistory = false" type="text" placeholder="Cari projek" class="bg-transparent w-full outline-none text-sm py-1"></div>
                        <div x-show="openHistory" class="absolute left-0 right-0 mt-2 bg-white border rounded-2xl shadow-xl z-50 overflow-hidden"><p class="px-4 py-2 text-[10px] uppercase font-bold text-gray-400 border-b">Pencarian Terakhir</p>@foreach($searchHistory as $h)<a href="#" class="block px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 border-b border-gray-50">{{ $h }}</a>@endforeach</div>
                    </div>
                    <div class="relative" x-data="{ openStatus: false }">
                        <button @click="openStatus = !openStatus" class="bg-gray-50 px-6 py-3 rounded-full text-sm font-bold text-gray-700 border flex items-center space-x-2 hover:bg-gray-100"><span>Status</span><i class="fas fa-chevron-down text-[10px]"></i></button>
                        <div x-show="openStatus" @click.outside="openStatus = false" class="absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-xl z-50"><a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">On Progress</a><a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50 border-b">Selesai</a><a href="#" class="block px-4 py-3 text-sm hover:bg-gray-50">Belum Approve</a></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    @foreach($projects as $p)
    <div class="bg-white rounded-[2rem] border p-8 shadow-sm hover:shadow-md transition">
        <div>
            <!-- Klik Judul ke Dekomposisi -->
            <a href="{{ route('dekomposisi', $p['id']) }}" class="text-xl font-bold hover:text-blue-600 transition">
                {{ $p['name'] }}
            </a>
            
            <div class="flex items-center space-x-3 mt-3 mb-5">
                <span class="bg-blue-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase">Berlangsung</span>
                <span class="text-sm font-bold text-gray-400">{{ $p['progress'] }}% Complete</span>
            </div>

            <p class="text-gray-500 text-sm line-clamp-2 mb-6">{{ $p['description'] }}</p>
            
            <div class="w-full bg-gray-100 h-2.5 rounded-full mb-8">
                <div class="bg-gray-800 h-full" style="width: {{ $p['progress'] }}%"></div>
            </div>
        </div>

        <div class="flex flex-col space-y-6">
            <div class="flex items-center">
                <div class="flex -space-x-3">
                    @foreach(array_slice($p['members'], 0, 3) as $m)
                        <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-blue-600">{{ $m }}</div>
                    @endforeach
                </div>
            </div>
                        <!-- Klik Tombol ke Dekomposisi -->
                        <a href="{{ route('dekomposisi', $p['id']) }}" class="w-full border py-2.5 rounded-full text-center text-xs font-bold hover:bg-gray-50 transition">
                            Lihat Detail
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            </div>
        </main>
    </div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
     class="fixed bottom-10 right-10 bg-green-600 text-white px-8 py-4 rounded-2xl shadow-2xl z-[100] flex items-center space-x-3 transition-all">
    <i class="fas fa-check-circle text-xl"></i>
    <span class="font-bold">{{ session('success') }}</span>
</div>
@endif
</body>
</html>