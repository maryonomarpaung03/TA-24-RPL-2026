@php
    $u = auth()->user();
    $displayName = $u
        ? (trim($u->displayName()) !== '' ? $u->displayName() : ($u->email ?? 'User'))
        : 'User';

    $notifCount = (int) ($notif_count ?? 0);

    $initials = 'U';
    if ($u) {
        $src = trim($u->displayName()) !== '' ? $u->displayName() : (string) ($u->email ?? '');
        $words = preg_split('/\s+/', trim($src), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) >= 2) {
            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } elseif (count($words) === 1) {
            $initials = strtoupper(substr($words[0], 0, 2));
        }
    }
@endphp

<header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">

    <!-- Welcome -->
    <div>
        <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">
            selamat datang,
        </p>

        <h3 class="font-bold text-gray-800 text-sm">
            {{ $displayName }}
        </h3>
    </div>

    <!-- Right Menu -->
    <div class="flex items-center space-x-6">

        <!-- Notification -->
        <a href="{{ route('notifikasi') }}"
           class="relative p-2">

            <i class="fas fa-bell text-gray-300 text-2xl"></i>

            @if($notifCount > 0)
                <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">
                    {{ $notifCount }}
                </span>
            @endif
        </a>

        <!-- Avatar -->
        <a
    href="{{ route('profil') }}"
    class="block"
>
    <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm overflow-hidden">

        @if(!empty($loggedUser?->profile_photo))
            <img
                src="{{ asset($loggedUser->profile_photo) }}"
                class="w-full h-full object-cover"
            >
        @else
            {{ $initials }}
        @endif

    </div>
</a>

    </div>

</header>
