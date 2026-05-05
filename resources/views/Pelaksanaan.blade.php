<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaksanaan & Evaluasi - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans" x-data="{ 
    sidebarOpen: true, 
    commentModal: false, 
    editModal: false,
    selectedTask: '',
    activeColumn: null
}">

    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col shadow-sm z-50">
            <div class="p-6 text-center">
                <a href="{{ route('dashboard') }}"><h1 class="text-2xl font-bold text-blue-600 uppercase">Delpro</h1></a>
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
                <span x-show="sidebarOpen">Collapse</span><i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas transition-transform"></i>
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
                    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm shadow-sm">{{ $user['initials'] }}</div>
                </div>
            </header>

            <div class="p-6 space-y-6">
                <!-- Judul & Breadcrumb -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 leading-tight">{{ $namaProjek }}</h2>
                        <nav class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-tight">
                            projek saya/ <span class="text-blue-500">Pelaksanaan dan evaluasi</span>
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
                                ['icon' => 'fa-project-diagram', 'label' => 'Pelaksanaan & Evaluasi', 'r' => '#'],
                                ['icon' => 'fa-clipboard-check', 'label' => 'Penilaian', 'r' => 'penilaian-kelompok']
                            ];
                        @endphp
                        @foreach($menus as $index => $m)
                            <div class="relative group">
                                <a href="{{ $m['r'] != '#' ? route($m['r'], $id) : '#' }}" 
                                   class="{{ $index == 3 ? 'bg-blue-200' : 'bg-white' }} p-5 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-center transition hover:shadow-md">
                                    <i class="fas {{ $m['icon'] }} text-2xl text-gray-700"></i>
                                </a>
                                <div class="absolute left-24 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-[10px] px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-50 pointer-events-none shadow-lg">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- KANBAN BOARD -->
                    <div class="flex-1">
                        <!-- Anggota Aktif -->
                        <div class="flex justify-end mb-4">
                            <div class="flex -space-x-2">
                                @foreach(['DS', 'NT', 'RH'] as $av)
                                <div class="w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-[9px] font-bold text-blue-600 shadow-sm">{{ $av }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-6">
                            @foreach([['key'=>'todo','label'=>'Belum Dikerjakan','color'=>'blue-600'], ['key'=>'doing','label'=>'Belum Dikerjakan','color'=>'yellow-400'], ['key'=>'done','label'=>'Selesai','color'=>'green-500']] as $col)
                            <div class="bg-gray-200/80 rounded-[2rem] p-6 flex flex-col h-[600px]">
                                <div class="flex items-center space-x-2 mb-6">
                                    <div class="w-3 h-3 rounded-full bg-{{ $col['color'] }}"></div>
                                    <h4 class="text-sm font-bold text-gray-700">{{ $col['label'] }}</h4>
                                </div>

                                <div class="flex-1 space-y-4 overflow-y-auto">
                                    @foreach($kanban[$col['key']] as $task)
                                    <div class="bg-white rounded-2xl p-4 shadow-sm relative group transition hover:shadow-md">
                                        <div class="flex justify-between items-start mb-2">
                                            <p class="text-[11px] font-bold text-gray-800 leading-tight w-4/5">{{ $task['name'] }}</p>
                                            <button @click="editModal = true; selectedTask = '{{ $task['name'] }}'" class="text-gray-300 hover:text-blue-500 transition"><i class="fas fa-edit text-xs"></i></button>
                                        </div>
                                        <div class="flex items-center justify-between mt-4">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 text-[8px] flex items-center justify-center font-bold text-blue-600 border border-white">{{ $task['creator'] }}</div>
                                                <span class="text-[8px] px-2 py-0.5 rounded-full font-bold text-white {{ $task['level'] == 'Sulit' ? 'bg-red-500' : ($task['level'] == 'Sedang' ? 'bg-blue-500' : 'bg-green-500') }}">{{ $task['level'] }}</span>
                                            </div>
                                            <button @click="commentModal = true" class="text-[8px] bg-gray-400 text-white px-2 py-0.5 rounded-full font-bold hover:bg-gray-600">Komentar</button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="mt-4" x-data="{ adding: false }">
                                    <button x-show="!adding" @click="adding = true" class="text-blue-500 text-[11px] font-bold hover:underline">Tambah tugas</button>
                                    <input x-show="adding" @focusout="adding = false" @keydown.enter="adding = false" type="text" placeholder="Ketik tugas & enter" class="w-full bg-white border border-blue-300 rounded-xl px-4 py-2 text-xs outline-none shadow-inner">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL KOMENTAR -->
    <div x-show="commentModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl p-8 w-96 shadow-2xl relative" @click.outside="commentModal = false">
            <button @click="commentModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-black"><i class="fas fa-times"></i></button>
            <h3 class="text-lg font-bold mb-4">Komentar</h3>
            <textarea placeholder="Ketik komentar anda..." class="w-full bg-gray-50 border rounded-xl p-4 text-xs outline-none focus:border-blue-400 h-32 resize-none"></textarea>
            <div class="flex justify-end space-x-3 mt-6">
                <button @click="commentModal = false" class="bg-gray-200 text-gray-600 px-6 py-2 rounded-lg text-xs font-bold transition hover:bg-gray-300">Batal</button>
                <button @click="commentModal = false" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-xs font-bold shadow-md hover:bg-blue-700">Komen</button>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT TUGAS -->
    <div x-show="editModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-10 w-full max-w-lg shadow-2xl relative" @click.outside="editModal = false">
            <button @click="editModal = false" class="absolute top-6 right-6 text-gray-400 hover:text-black text-xl"><i class="fas fa-times"></i></button>
            <h3 class="text-2xl font-bold mb-8 text-center text-gray-800">Edit Tugas</h3>
            <form class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Nama Tugas</label>
                    <input type="text" x-model="selectedTask" class="w-full bg-gray-50 border rounded-xl px-4 py-3 text-sm outline-none focus:border-blue-400 shadow-inner">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Tingkat Kesulitan</label>
                    <select class="w-full bg-gray-50 border rounded-xl px-4 py-3 text-sm outline-none appearance-none cursor-pointer">
                        <option>Mudah</option><option>Sedang</option><option>Sulit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Upload Bukti (.png)</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-blue-400 transition cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-2"></i>
                        <p class="text-[10px] text-gray-400 italic">Pilih file atau tarik gambar ke sini</p>
                    </div>
                </div>
                <div class="flex justify-center space-x-4 pt-4">
                    <button type="button" @click="editModal = false" class="bg-gray-100 text-gray-500 px-10 py-2.5 rounded-full text-xs font-bold hover:bg-gray-200 transition">Batal</button>
                    <button type="button" @click="editModal = false" class="bg-blue-600 text-white px-10 py-2.5 rounded-full text-xs font-bold shadow-lg hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>