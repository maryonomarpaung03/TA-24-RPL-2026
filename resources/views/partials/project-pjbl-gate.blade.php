@php
    $isDraft = $selected_project['is_draft'] ?? false;
    $isPending = $selected_project['is_under_review'] ?? false;
    $isOwner = (int) auth()->id() === (int) ($selected_project['created_by'] ?? 0);
@endphp

<div class="space-y-6">
    @if(session('pjbl_locked'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-lock mr-2"></i>{{ session('pjbl_locked') }}
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-[2rem] border border-slate-200 p-8 shadow-sm w-full">
        @if($isPending)
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <i class="fas fa-hourglass-half text-2xl"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-amber-600 mb-2">In Review</p>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ $selected_project['name'] }}</h2>
            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                Proyek belum dapat dilanjutkan karena sedang dalam <strong>review oleh dosen</strong>.
                Halaman PjBL (Problem Identification, Decomposition, Planning, dan seterusnya) akan terbuka setelah dosen menyetujui proyek ini.
            </p>
            <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-900 text-left mb-6">
                <p><span class="font-semibold">Dosen pengampu:</span> {{ $selected_project['lecturer_name'] ?? '-' }}</p>
                <p class="mt-1"><span class="font-semibold">Email:</span> {{ $selected_project['lecturer_email'] ?? '-' }}</p>
                @if(!empty($selected_project['submitted_at']))
                    <p class="mt-1"><span class="font-semibold">Diajukan:</span> {{ $selected_project['submitted_at']->format('d M Y H:i') }}</p>
                @endif
            </div>

            @if($isOwner)
            <div class="flex justify-center">
                <a href="{{ route('projek.edit', $selected_project['id']) }}"
                   class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-white px-5 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50 transition">
                    <i class="fas fa-edit"></i>
                    Edit & Ajukan Ulang
                </a>
            </div>
            @endif
        @elseif($isDraft)
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                <i class="fas fa-file-pen text-2xl"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500 mb-2">Draft</p>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ $selected_project['name'] }}</h2>
            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                Proyek masih berstatus <strong>draft</strong>. Perbarui data proyek lalu ajukan ke dosen untuk memulai tahap PjBL.
            </p>
            @if($isOwner)
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('projek.edit', $selected_project['id']) }}"
                   class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <i class="fas fa-edit"></i>
                    Edit Proyek
                </a>
                <form method="POST" action="{{ route('projek.submit', $selected_project['id']) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        <i class="fas fa-paper-plane"></i>
                        Ajukan ke Dosen
                    </button>
                </form>
                <form method="POST" action="{{ route('projek.destroy', $selected_project['id']) }}" class="inline"
                      onsubmit="return confirm('Hapus proyek draft &quot;{{ $selected_project['name'] }}&quot;? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-full border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition">
                        <i class="fas fa-trash"></i>
                        Hapus Proyek
                    </button>
                </form>
            </div>
            @endif
        @else
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <i class="fas fa-lock text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ $selected_project['name'] }}</h2>
            <p class="text-slate-600 text-sm">Proyek belum dapat mengakses tahap PjBL pada status saat ini.</p>
        @endif

        <a href="{{ route('my-project') }}" class="inline-block mt-8 text-sm font-semibold text-blue-600 hover:text-blue-700">
            ← Kembali ke Projek Saya
        </a>
    </div>
</div>
