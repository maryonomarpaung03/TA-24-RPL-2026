@if($canManage ?? false)
<div class="flex flex-wrap items-center gap-2 {{ $class ?? '' }}">
    <a href="{{ route('projek.edit', $projectId) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
        <i class="fas fa-pen text-[10px]"></i> Edit
    </a>
    <form method="POST"
          action="{{ route('projek.destroy', $projectId) }}"
          class="inline"
          onsubmit="return confirm('Apakah kamu yakin ingin menghapus proyek ini?');">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 transition">
            <i class="fas fa-trash text-[10px]"></i> Hapus
        </button>
    </form>
</div>
@endif
