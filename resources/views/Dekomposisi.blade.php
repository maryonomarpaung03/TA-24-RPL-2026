<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dekomposisi Masalah - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dot-grid { 
            background-image: radial-gradient(#d1d5db 1px, transparent 1px); 
            background-size: 20px 20px; 
        }
        /* Style untuk scrollbar vertikal pada komentar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true, zoom: 1 }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR UTAMA -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm z-50">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}">
                    <h1 class="text-2xl font-bold text-blue-600">DELPRO</h1>
                </a>
            </div>
            <nav class="flex-1 px-4 mt-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded-xl text-gray-500 hover:bg-gray-100">
                    <i class="fas fa-th-large w-6 text-center"></i>
                    <span x-show="sidebarOpen">Dashboard</span>
                </a>
                <a href="{{ route('projek-saya') }}" class="flex items-center space-x-3 p-3 rounded-xl bg-blue-100 text-blue-700 font-bold">
                    <i class="fas fa-project-diagram w-6 text-center"></i>
                    <span x-show="sidebarOpen">Projek Saya</span>
                </a>
            </nav>
            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t text-gray-400 hover:text-blue-600 flex items-center justify-center space-x-2">
                <span x-show="sidebarOpen">Collapse</span>
                <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas transition-transform"></i>
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div>
                    <p class="text-gray-400 text-xs tracking-widest uppercase font-bold">selamat datang,</p>
                    <h3 class="font-bold text-gray-800">{{ $user['name'] }}</h3>
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
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-6 space-y-6" x-data="{ 
                boxes: [
                    {id: 1, text: 'Tidak adanya validasi waktu absensi secara real-time'},
                    {id: 2, text: 'Sulitnya memantau kehadiran secara langsung oleh pihak terkait'},
                    {id: 3, text: 'Kurangnya transparansi data absensi bagi peserta dan pengelola'},
                    {id: 4, text: 'Kesalahan pencatatan (human error) dalam input data absensi'}
                ],
                rightBoxes: [
                    {id: 5, text: 'Proses absensi masih dilakukan secara manual (tulis tangan/kertas)'},
                    {id: 6, text: 'Terjadinya kecurangan seperti titip absen antar peserta'},
                    {id: 7, text: 'Proses rekap data absensi memakan waktu lama'},
                    {id: 8, text: 'Data absensi rentan hilang atau rusak'}
                ],
                addTopik() { this.boxes.push({id: Date.now(), text: ''}) },
                removeBox(id, side) { 
                    if(side === 'left') this.boxes = this.boxes.filter(b => b.id !== id);
                    else this.rightBoxes = this.rightBoxes.filter(b => b.id !== id);
                }
            }">
                
                <!-- 2 & 3. Breadcrumb & Judul Projek Dinamis -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            <a href="{{ route('projek-saya') }}" class="hover:text-blue-500">projek saya</a> / 
                            <span class="text-blue-500">dekomposisi masalah</span>
                        </nav>
                    </div>
                    <!-- 4. Gear Settings -->
                    <div class="relative" x-data="{ openGear: false }">
                        <button @click="openGear = !openGear" class="p-3 text-gray-400 hover:text-gray-600 text-2xl transition"><i class="fas fa-cog"></i></button>
                        <div x-show="openGear" @click.outside="openGear = false" class="absolute right-0 mt-2 w-48 bg-white border rounded-2xl shadow-xl z-50 overflow-hidden text-sm">
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b">Detail Projek</a>
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b">Edit Projek</a>
                            <a href="{{ route('projek-saya') }}" class="block px-4 py-3 text-red-500 hover:bg-red-50">Keluar</a>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-6 relative">
                    <!-- 5. Menu Urutan Ikon Kiri -->
                    <div class="w-24 space-y-4">
                        @php
                            $menus = [
                                ['icon' => 'fa-comments', 'color' => 'bg-blue-200', 'label' => 'Dekomposisi Masalah', 'route' => '#'],
                                ['icon' => 'fa-tasks', 'color' => 'bg-white', 'label' => 'Penyusunan Rencana', 'route' => route('penyusunan', $id)],
                                ['icon' => 'fa-calendar-alt', 'color' => 'bg-white', 'label' => 'Waktu Progres', 'route' => route('waktu-progres', $id)],
                                ['icon' => 'fa-project-diagram', 'color' => 'bg-white', 'label' => 'Pelaksanaan & Evaluasi', 'route' => route('pelaksanaan', $id)],
                                ['icon' => 'fa-clipboard-check', 'color' => 'bg-white', 'label' => 'Penilaian', 'route' => route('penilaian-kelompok', $id)]
                            ];
                        @endphp
                        @foreach($menus as $menu)
                        <div class="relative group">
                            <a href="{{ $menu['route'] }}" class="{{ $menu['color'] }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md cursor-pointer">
                                <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                            </a>
                            <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 shadow-lg">
                                {{ $menu['label'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- 6. LEMBAR KERJA DIAGRAM -->
                    <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[700px] relative">
                        <!-- 6c. Anggota Aktif -->
                        <div class="absolute top-6 right-6 flex -space-x-2 z-10">
                            @foreach(['DS', 'NT', 'RH'] as $member)
                            <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-blue-600 shadow-sm">{{ $member }}</div>
                            @endforeach
                        </div>

                        <!-- Area Diagram -->
                        <div class="flex-1 dot-grid overflow-auto p-10 relative">
                            <div class="flex items-center justify-center min-h-full transition-transform duration-300" :style="`transform: scale(${zoom})`">
                                
                                <!-- Sisi Kiri (Sub Masalah) -->
                                <div class="flex flex-col space-y-4 items-end">
                                    <template x-for="box in boxes" :key="box.id">
                                        <div class="bg-white border-2 border-blue-400 rounded-xl p-3 w-56 relative group shadow-sm">
                                            <textarea x-model="box.text" class="w-full text-[10px] outline-none bg-transparent resize-none h-12 leading-tight" placeholder="Ketik sub masalah..."></textarea>
                                            <button @click="removeBox(box.id, 'left')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-md hover:bg-red-600">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <!-- Garis & Kotak Pusat -->
                                <div class="flex items-center">
                                    <div class="w-12 h-0.5 bg-blue-200"></div>
                                    <div class="bg-white border-2 border-blue-600 rounded-2xl p-6 w-64 shadow-lg text-center font-bold text-sm text-blue-900">
                                        Ketidakefisienan dan ketidakakuratan sistem absensi
                                    </div>
                                    <div class="w-12 h-0.5 bg-blue-200"></div>
                                </div>

                                <!-- Sisi Kanan (Sub Masalah) -->
                                <div class="flex flex-col space-y-4 items-start">
                                    <template x-for="box in rightBoxes" :key="box.id">
                                        <div class="bg-white border-2 border-blue-400 rounded-xl p-3 w-56 relative group shadow-sm">
                                            <textarea x-model="box.text" class="w-full text-[10px] outline-none bg-transparent resize-none h-12 leading-tight" placeholder="Ketik sub masalah..."></textarea>
                                            <button @click="removeBox(box.id, 'right')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-md hover:bg-red-600">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <!-- Indikator Anggota di salah satu kotak -->
                                            <template x-if="box.id === 5">
                                                <div class="absolute -right-6 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-[8px] font-bold text-blue-600 border border-white shadow-sm">RH</div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6d. Menu Samping Lembar Kerja -->
                    <div class="w-64 flex flex-col space-y-6">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                            <ul class="space-y-4">
                                <li @click="addTopik()" class="flex justify-between items-center text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 transition">
                                    <span>Tambah Topik</span><i class="fas fa-plus-circle text-lg"></i>
                                </li>
                                <li class="flex justify-between items-center text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 border-t pt-4 transition group">
                                    <span>Daftar Diagram</span><i class="fas fa-list text-lg text-gray-400 group-hover:text-blue-400"></i>
                                </li>
                                <li @click="zoom += 0.1" class="flex justify-between items-center text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 border-t pt-4 transition">
                                    <span>Zoom In</span><i class="fas fa-search-plus text-lg text-gray-400"></i>
                                </li>
                                <li @click="zoom = Math.max(0.5, zoom - 0.1)" class="flex justify-between items-center text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 border-t pt-4 transition">
                                    <span>Zoom Out</span><i class="fas fa-search-minus text-lg text-gray-400"></i>
                                </li>
                                <li class="flex justify-between items-center text-xs font-bold text-gray-700 cursor-pointer hover:text-blue-600 border-t pt-4 relative group" x-data="{ openDl: false }">
                                    <span @click="openDl = !openDl">Download .PNG</span><i class="fas fa-download text-lg text-gray-400 group-hover:text-blue-400"></i>
                                    <div x-show="openDl" @click.outside="openDl = false" class="absolute top-10 right-0 w-full bg-white border rounded-xl shadow-xl z-50 overflow-hidden">
                                        <a href="#" class="block px-4 py-2 text-[10px] hover:bg-gray-50 border-b">Diagram Saat Ini</a>
                                        <a href="#" class="block px-4 py-2 text-[10px] hover:bg-gray-50">Semua Diagram</a>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- 7. Sidebar Komentar -->
                        <div class="flex-1 bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex flex-col min-h-[300px]">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-4 tracking-widest border-b pb-2 flex items-center">
                                <i class="fas fa-comment-alt mr-2"></i> Komentar
                            </h4>
                            <div class="flex-1 overflow-y-auto space-y-4 mb-4 custom-scrollbar pr-1">
                                <div class="flex space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-blue-600">NT</div>
                                    <div class="bg-gray-50 rounded-2xl p-3 text-[10px] text-gray-600 shadow-sm">
                                        <p class="font-bold mb-1 text-blue-800">NT</p>
                                        Bagian validasi real-time sudah saya tambahkan ya teman-teman.
                                    </div>
                                </div>
                            </div>
                            <!-- Input Komentar -->
                            <div class="relative">
                                <input type="text" placeholder="Ketik komentar..." class="w-full bg-gray-50 border border-gray-200 rounded-full py-2.5 px-5 text-xs outline-none focus:border-blue-300 transition shadow-inner">
                                <button class="absolute right-2 top-1/2 -translate-y-1/2 text-blue-500 hover:text-blue-700 p-2">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>