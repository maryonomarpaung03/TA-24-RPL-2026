{{-- Field konfigurasi kolom kanban. Butuh:
     $model = nama objek Alpine ('addCol' | 'editCol') dgn: label,color,description,is_done,requires_approval,checklist[]
     $colorOptions = daftar token warna. --}}

<label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kolom</label>
<input type="text" name="label" x-model="{{ $model }}.label" required maxlength="60"
       placeholder="mis. Review, Testing"
       class="w-full border rounded-xl p-3 mb-4 outline-none focus:border-blue-400">

<input type="hidden" name="color" :value="{{ $model }}.color">
<label class="block text-sm font-semibold text-gray-700 mb-2">Warna</label>
<div class="flex flex-wrap gap-2 mb-4">
    @foreach($colorOptions as $c)
    <button type="button" @click="{{ $model }}.color = '{{ $c }}'"
            class="w-8 h-8 rounded-full bg-{{ $c }} transition"
            :class="{{ $model }}.color === '{{ $c }}' ? 'ring-2 ring-offset-2 ring-gray-800' : ''"></button>
    @endforeach
</div>

<label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Kolom <span class="text-gray-400 font-normal">(opsional)</span></label>
<textarea name="description" x-model="{{ $model }}.description" maxlength="500" rows="2"
          placeholder="Jelaskan makna tahap ini..."
          class="w-full border rounded-xl p-3 mb-4 outline-none focus:border-blue-400 resize-none"></textarea>

<div class="space-y-2 mb-4">
    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
        <input type="checkbox" name="is_done" value="1" x-model="{{ $model }}.is_done" class="mt-0.5">
        <span>
            <span class="font-semibold">Tandai sebagai kolom "Selesai" (Done)</span>
            <span class="block text-[11px] text-gray-400">Tugas di kolom ini dihitung selesai untuk progres &amp; kontribusi.</span>
        </span>
    </label>
    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
        <input type="checkbox" name="requires_approval" value="1" x-model="{{ $model }}.requires_approval" class="mt-0.5">
        <span>
            <span class="font-semibold">Perlu persetujuan Dosen</span>
            <span class="block text-[11px] text-gray-400">Tugas ditahan sampai Dosen menyetujui sebelum masuk kolom ini.</span>
        </span>
    </label>
</div>

<label class="block text-sm font-semibold text-gray-700 mb-1">Checklist Definition of Done <span class="text-gray-400 font-normal">(opsional)</span></label>
<p class="text-[11px] text-gray-400 mb-2">Semua item wajib dicentang sebelum tugas boleh dipindahkan ke kolom ini.</p>
<div class="space-y-2 mb-2">
    <template x-for="(item, i) in {{ $model }}.checklist" :key="i">
        <div class="flex items-center gap-2">
            <input type="text" name="checklist[]" x-model="{{ $model }}.checklist[i]" maxlength="200"
                   placeholder="mis. Sudah dites, Sudah direview rekan"
                   class="flex-1 border rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400">
            <button type="button" @click="{{ $model }}.checklist.splice(i, 1)"
                    class="text-red-400 hover:text-red-600 shrink-0 px-1"><i class="fas fa-times"></i></button>
        </div>
    </template>
</div>
<button type="button" @click="{{ $model }}.checklist.push('')"
        class="text-blue-600 text-xs font-bold hover:underline">
    <i class="fas fa-plus mr-1"></i>Tambah item checklist
</button>
