<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian - DELPRO</title>
    <!-- Pastikan Library Ini Terload -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true, tab: 'kelompok' }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm z-50">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}"><h1 class="text-2xl font-bold text-blue-600">DELPRO</h1></a>
            </div>
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
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div>
                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">selamat datang,</p>
                    <h3 class="font-bold text-gray-800 text-sm">{{ $user['name'] }}</h3>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative"><i class="fas fa-bell text-gray-300 text-2xl"></i><span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span></div>
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-6 space-y-6">
                <!-- Project Header -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
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

                        <!-- Card Container -->
                        <div class="bg-white rounded-[2.5rem] p-12 shadow-sm border border-gray-100 max-w-4xl">
                            <form action="#" class="space-y-10">
                                <!-- Bagian 1: Penilaian Kelompok -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Penilaian Kelompok</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <input type="number" min="10" max="100" class="border border-gray-400 rounded-lg p-2 w-16 text-center outline-none focus:border-blue-400 shadow-inner">
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
                                            @foreach($anggota as $nama)
                                            <tr class="border-b border-gray-400 last:border-b-0">
                                                <td class="p-3 border-r border-gray-400 font-medium">{{ $nama }}</td>
                                                <td class="p-1">
                                                    <input type="number" min="10" max="100" class="w-full p-2 outline-none text-center bg-transparent">
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>

                                <!-- Bagian 3: Refleksi -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Refleksi</label>
                                    <hr class="border-gray-400 w-full mb-4">
                                    <textarea maxlength="500" placeholder="Ketikkkan refleksi anda disini..." 
                                              class="w-full border border-gray-400 rounded-2xl p-4 h-28 resize-none outline-none focus:border-blue-400 transition text-xs italic shadow-inner"></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-end space-x-4 pt-4">
                                    <button type="button" class="bg-gray-300 px-8 py-2 rounded-full text-xs font-bold text-gray-700 hover:bg-gray-400 transition">Batal</button>
                                    <button type="submit" class="bg-blue-600 px-8 py-2 rounded-full text-xs font-bold text-white hover:bg-blue-700 transition shadow-lg shadow-blue-200">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>