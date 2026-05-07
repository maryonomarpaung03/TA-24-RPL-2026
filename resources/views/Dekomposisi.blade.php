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
        @include('partials.sidebar')

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-full mx-auto px-6 py-8">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col md:flex-row items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 font-semibold mb-2">Projects / Dekomposisi</p>
                            <h1 class="text-3xl font-bold text-slate-900">Dekomposisi Masalah</h1>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <i class="fas fa-bell text-2xl text-gray-400"></i>
                                @if($user['notif_count'] > 0)
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">{{ $user['notif_count'] }}</span>
                                @endif
                            </div>
                            <div class="h-10 w-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-sm">{{ $user['initials'] }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[1.7fr_1fr] gap-6">
                        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 p-6">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.3em] text-blue-500 font-bold mb-2">Dekomposisi Masalah</p>
                                        <h2 class="text-2xl font-bold text-slate-900">Visualisasi dan pemecahan masalah inti proyek Anda.</h2>
                                    </div>
                                    <div class="rounded-full bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 uppercase tracking-[0.2em]">Problem Decomposition</div>
                                </div>
                            </div>

                            <div class="relative bg-slate-50 p-6">
                                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.08),_transparent_25%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.08),_transparent_20%)] pointer-events-none"></div>
                                <div class="dot-grid relative rounded-[1.75rem] bg-white border border-slate-200 p-6 overflow-hidden" style="min-height:680px;">
                                    <div class="absolute top-5 right-6 flex -space-x-2 z-20">
                                        @foreach(['DS','NT','RH'] as $member)
                                        <div class="h-10 w-10 rounded-full bg-blue-100 border border-white flex items-center justify-center text-sm font-bold text-blue-700 shadow-sm">{{ $member }}</div>
                                        @endforeach
                                    </div>

                                    <div class="flex items-center justify-center h-full">
                                        <div class="flex items-center gap-10 w-full">
                                            <div class="flex flex-col gap-6 items-end">
                                                <template x-for="box in boxes" :key="box.id">
                                                    <div class="min-w-[240px] rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                                                        <p class="text-sm leading-6 text-slate-800" x-text="box.text"></p>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="flex flex-col items-center gap-6">
                                                <div class="w-20 h-[1px] bg-blue-200"></div>
                                                <div class="rounded-3xl border-2 border-blue-600 bg-blue-50 px-8 py-6 text-center shadow-lg">
                                                    <p class="text-sm font-semibold text-blue-900">Ketidakefisienan dan ketidakakuratan sistem absensi</p>
                                                </div>
                                                <div class="w-20 h-[1px] bg-blue-200"></div>
                                            </div>

                                            <div class="flex flex-col gap-6 items-start">
                                                <template x-for="box in rightBoxes" :key="box.id">
                                                    <div class="min-w-[240px] rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                                                        <p class="text-sm leading-6 text-slate-800" x-text="box.text"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="space-y-6">
                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Tambah Topik</h3>
                                <button @click="boxes.push({id: Date.now(), text: 'Masalah baru yang teridentifikasi'})" class="flex w-full items-center justify-between rounded-3xl bg-slate-100 px-4 py-4 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
                                    <span>Tambah Topik</span>
                                    <i class="fas fa-plus-circle text-lg"></i>
                                </button>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Daftar Diagram</h3>
                                <ul class="space-y-4 text-sm text-slate-700">
                                    <li class="flex items-center justify-between gap-3 rounded-3xl bg-slate-50 px-4 py-4">
                                        <span>Sub-masalah utama</span>
                                        <i class="fas fa-list text-slate-400"></i>
                                    </li>
                                    <li class="flex items-center justify-between gap-3 rounded-3xl bg-slate-50 px-4 py-4">
                                        <span>Hubungan sebab-akibat</span>
                                        <i class="fas fa-list text-slate-400"></i>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Zoom & Export</h3>
                                <div class="space-y-4 text-sm text-slate-700">
                                    <button @click="zoom = Math.min(2, zoom + 0.1)" class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Zoom In</span>
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                    <button @click="zoom = Math.max(0.5, zoom - 0.1)" class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Zoom Out</span>
                                        <i class="fas fa-search-minus"></i>
                                    </button>
                                    <button class="flex w-full items-center justify-between rounded-3xl bg-slate-50 px-4 py-4 hover:bg-slate-100 transition">
                                        <span>Download .PNG</span>
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="bg-white rounded-[1.75rem] border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm uppercase tracking-[0.3em] text-slate-400 font-semibold mb-4">Komentar</h3>
                                <div class="space-y-4">
                                    <div class="rounded-3xl bg-slate-50 p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700">NT</div>
                                            <div>
                                                <p class="text-xs font-semibold text-slate-900">NT</p>
                                                <p class="text-sm text-slate-600">Bagian validasi real-time sudah saya tambahkan ya teman-teman.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <input type="text" placeholder="Ketik komentar..." class="w-full rounded-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>