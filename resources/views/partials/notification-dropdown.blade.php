@php
    $recentNotifications = $recent_notifications ?? collect();
    $notifCount = (int) ($notif_count ?? 0);
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button"
            @click="open = !open"
            class="relative p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition"
            aria-label="Notifikasi"
            :aria-expanded="open">
        <i class="fas fa-bell text-2xl"></i>
        @if($notifCount > 0)
        <span class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[10px] rounded-full min-w-[1.25rem] h-5 px-1 flex items-center justify-center border-2 border-white font-bold">
            {{ $notifCount > 9 ? '9+' : $notifCount }}
        </span>
        @endif
    </button>

    <div x-show="open"
         x-cloak
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute right-0 mt-2 w-[min(100vw-2rem,22rem)] sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <div>
                <p class="text-sm font-bold text-slate-900">Notifikasi</p>
                @if($notifCount > 0)
                <p class="text-[10px] text-slate-500">{{ $notifCount }} belum dibaca</p>
                @endif
            </div>
            @if($notifCount > 0)
            <form method="POST" action="{{ route('notifikasi.read-all') }}" @click.stop>
                @csrf
                <button type="submit" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">
                    Tandai semua dibaca
                </button>
            </form>
            @endif
        </div>

        <div class="max-h-[min(70vh,24rem)] overflow-y-auto divide-y divide-slate-100">
            @forelse($recentNotifications as $note)
                @include('partials.notification-item', ['note' => $note, 'compact' => true])
            @empty
            <div class="px-4 py-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">Belum ada notifikasi</p>
                <p class="text-xs text-slate-400 mt-1">Pemberitahuan proyek akan muncul di sini.</p>
            </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 bg-white px-4 py-3">
            <a href="{{ route('notifikasi') }}"
               @click="open = false"
               class="flex items-center justify-center gap-2 w-full rounded-xl bg-slate-900 text-white text-xs font-bold py-2.5 hover:bg-slate-800 transition">
                Lihat semua notifikasi
                <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
