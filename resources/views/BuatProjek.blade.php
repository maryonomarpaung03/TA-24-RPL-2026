<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Projek - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}">
                    <h1 class="text-2xl font-bold text-blue-600">DELPRO</h1>
                    <p x-show="sidebarOpen" class="text-gray-500 text-[10px] uppercase tracking-widest font-semibold">Monitoring Project</p>
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
                <span x-show="sidebarOpen" class="text-sm">Collapse</span>
                <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i>
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            <!-- HEADER -->
            <header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
                <div>
                    <p class="text-gray-400 text-xs">selamat datang,</p>
                    <h3 class="font-bold text-gray-800">{{ $user['name'] }}</h3>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('notifikasi') }}" class="relative p-2"><i class="fas fa-bell text-gray-300 text-2xl"></i><span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span></a>
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-8 flex flex-col items-center">
                <div class="w-full max-w-4xl">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Buat Projek</h2>

                    <!-- Form Card -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 p-10 shadow-sm">
                        <!-- Petunjuk Pengisian -->
                        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-8">
                            <h4 class="font-bold text-gray-800 mb-3">Petunjuk Pengisian Form Buat Projek</h4>
                            <ul class="text-sm text-gray-600 space-y-2 list-decimal list-inside leading-relaxed">
                                <li>Judul harus singkat, jelas, dan sesuai dengan ruang lingkup pembelajaran</li>
                                <li>Masalah utama adalah hal yang benar-benar terjadi dan bisa dibuktikan secara nyata (bukan asumsi)</li>
                                <li>Deskripsi masalah menjelaskan konteks dan mengapa masalah itu penting untuk diselesaikan, sertakan bukti, contoh, atau observasi (Jika ada)</li>
                            </ul>
                        </div>

                        <form action="{{ route('simpan-projek') }}" method="POST" class="space-y-6">
                            @csrf
                            <!-- Judul Proyek -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Judul Proyek</label>
                                <input type="text" name="judul" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 transition" placeholder="Masukkan judul proyek anda">
                            </div>

                            <!-- Masalah Utama -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Masalah utama</label>
                                <input type="text" name="masalah" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 transition" placeholder="Apa masalah utama yang ingin diselesaikan?">
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                                <textarea name="deskripsi" rows="6" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-4 outline-none focus:border-blue-400 transition resize-none" placeholder="Jelaskan deskripsi proyek secara detail..."></textarea>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="flex justify-end space-x-4 pt-4">
                                <a href="{{ route('projek-saya') }}" class="bg-gray-300 text-gray-700 px-8 py-2 rounded-full font-bold text-sm hover:bg-gray-400 transition">Batal</a>
                                <button type="submit" class="bg-blue-600 text-white px-8 py-2 rounded-full font-bold text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-200">Tambah Tugas</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>