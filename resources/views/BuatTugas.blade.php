<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tugas - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
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

        <!-- MAIN -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div><p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">selamat datang,</p><h3 class="font-bold text-gray-800">{{ $user['name'] }}</h3></div>
                <div class="flex items-center space-x-6 text-2xl">
                    <div class="relative"><i class="fas fa-bell text-gray-300"></i><span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span></div>
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm shadow-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-6 space-y-6">
                <!-- Judul Projek & Gear -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $namaProjek }}</h2>
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
                    <!-- Navigasi Ikon Samping -->
                    <div class="w-24 space-y-4">
                        @foreach([['icon'=>'fa-comments','active'=>false], ['icon'=>'fa-tasks','active'=>true], ['icon'=>'fa-calendar-alt','active'=>false], ['icon'=>'fa-project-diagram','active'=>false], ['icon'=>'fa-clipboard-check','active'=>false]] as $menu)
                        <div class="bg-{{ $menu['active'] ? 'blue-200' : 'white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center cursor-pointer">
                            <i class="fas {{ $menu['icon'] }} text-2xl text-gray-700"></i>
                        </div>
                        @endforeach
                    </div>

                    <!-- FORM TAMBAH TUGAS -->
                    <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-gray-100 p-12">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-3xl font-bold text-gray-800">Tambah Tugas</h3>
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-blue-600">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>

                        <form action="{{ route('simpan-tugas', $id) }}" method="POST" class="space-y-8 max-w-4xl">
                            @csrf
                            <!-- Judul Tugas -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Judul Tugas</label>
                                <div class="col-span-3">
                                    <input type="text" name="judul_tugas" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 outline-none focus:border-blue-400" required>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="grid grid-cols-4 items-start">
                                <label class="text-sm font-bold text-gray-700 pt-2">Deskripsi Tugas</label>
                                <div class="col-span-3">
                                    <textarea name="deskripsi" rows="5" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 resize-none" required></textarea>
                                </div>
                            </div>

                            <!-- Tanggal -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Tanggal Mulai</label>
                                <div class="col-span-1 relative flex items-center">
                                    <input type="date" name="tgl_mulai" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 outline-none focus:border-blue-400 bg-white" required>
                                </div>
                                <label class="text-sm font-bold text-gray-700 text-center">Tanggal Selesai</label>
                                <div class="col-span-1 relative flex items-center">
                                    <input type="date" name="tgl_selesai" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 outline-none focus:border-blue-400 bg-white" required>
                                </div>
                            </div>

                            <!-- Penanggung Jawab -->
                            <div class="grid grid-cols-4 items-center">
                                <label class="text-sm font-bold text-gray-700">Penanggung Jawab</label>
                                <div class="col-span-1 relative">
                                    <select name="pj" class="w-full appearance-none border-2 border-gray-200 rounded-xl px-4 py-2 outline-none focus:border-blue-400 bg-white cursor-pointer" required>
                                        <option value="">Pilih Anggota</option>
                                        @foreach($members as $m)
                                            <option value="{{ $m }}">{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="flex justify-end space-x-4 pt-6">
                                <a href="{{ route('penyusunan', $id) }}" class="bg-gray-300 text-gray-700 px-8 py-2 rounded-lg font-bold text-xs hover:bg-gray-400 transition">Batal</a>
                                <button type="submit" class="bg-blue-500 text-white px-8 py-2 rounded-lg font-bold text-xs hover:bg-blue-600 transition shadow-lg shadow-blue-100">Tambah Tugas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>