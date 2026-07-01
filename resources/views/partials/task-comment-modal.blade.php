{{-- Popup komentar tugas. Butuh state Alpine di ancestor:
     commentModal (bool) & commentTask ({ id, name, comments: [] }). --}}
<div x-show="commentModal" x-cloak
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl" @click.outside="commentModal = false">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-bold text-gray-800">Komentar Tugas</h3>
            <button type="button" @click="commentModal = false" class="text-gray-400 hover:text-black">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="text-xs text-gray-500 mb-4" x-text="commentTask.name"></p>

        {{-- Daftar komentar --}}
        <div class="max-h-60 overflow-y-auto space-y-2 mb-4 pr-1">
            <template x-if="!commentTask.comments || commentTask.comments.length === 0">
                <p class="text-sm text-gray-400 italic py-2">Belum ada komentar pada tugas ini.</p>
            </template>
            <template x-for="(c, i) in (commentTask.comments || [])" :key="i">
                <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2">
                    <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500">
                        <span class="font-semibold text-gray-700" x-text="c.from"></span>
                        <span class="shrink-0" x-text="c.time"></span>
                    </div>
                    <p class="text-sm text-gray-700 mt-0.5 whitespace-pre-line" x-text="c.text"></p>
                </div>
            </template>
        </div>

        {{-- Input komentar baru --}}
        <form method="POST" :action="`{{ url('projek/'.$id.'/tugas') }}/${commentTask.id}/komentar`">
            @csrf
            <textarea name="komentar" required rows="3" placeholder="Tulis komentar..."
                      class="w-full bg-gray-50 border rounded-xl p-3 text-sm outline-none focus:border-blue-400 resize-none"></textarea>
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" @click="commentModal = false"
                        class="bg-gray-200 text-gray-600 px-5 py-2 rounded-lg text-xs font-bold hover:bg-gray-300">Tutup</button>
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg text-xs font-bold shadow-md hover:bg-blue-700">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim Komentar
                </button>
            </div>
        </form>
    </div>
</div>
