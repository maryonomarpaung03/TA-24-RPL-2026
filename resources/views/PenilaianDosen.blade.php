<div class="p-6 space-y-6">
    <!-- Header & Sidebar Ikon sama -->
    <div class="flex-1">
        <div class="flex space-x-8 border-b mb-10 text-sm font-bold text-gray-400">
            <a href="{{ route('penilaian-kelompok', $id) }}">Penilaian Kelompok</a>
            <span class="text-blue-600 border-b-2 border-blue-600 pb-2">Penilaian Dosen</span>
        </div>

        <div class="bg-white rounded-[2rem] p-10 shadow-sm border max-w-2xl">
            <div class="space-y-6">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase">Nilai dari Dosen</h4>
                    <p class="text-5xl font-black text-blue-600 mt-2">{{ $nilaiDosen['angka'] }}</p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase border-b pb-2">Catatan Dosen</h4>
                    <p class="text-sm text-gray-600 italic mt-4">"{{ $nilaiDosen['catatan'] }}"</p>
                </div>
                <p class="text-[10px] text-red-400 font-bold italic">*Hanya dapat dilihat. Penilaian bersifat mutlak.</p>
            </div>
        </div>
    </div>
</div>