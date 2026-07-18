@php
    $u = auth()->user();
    $displayName = $u
        ? (trim($u->displayName()) !== '' ? $u->displayName() : ($u->email ?? 'User'))
        : 'User';

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

    $profilePhotoUrl = null;
    if (! empty($loggedUser?->profile_photo)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($loggedUser->profile_photo)) {
        // Gunakan URL relatif agar avatar tetap menunjuk ke server yang sedang
        // dibuka pengguna, meski APP_URL berbeda dengan alamat development.
        $profilePhotoUrl = '/storage/' . ltrim($loggedUser->profile_photo, '/')
            . '?v=' . optional($loggedUser->updated_at)->timestamp;
    }
@endphp

<header class="bg-white px-6 py-3 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">

    <!-- Welcome -->
    <div>
        <p class="text-gray-400 text-xs uppercase font-bold tracking-widest">
            selamat datang,
        </p>

        <h3 class="font-bold text-gray-800 text-base">
            {{ $displayName }}
        </h3>
    </div>

    <!-- Right Menu -->
    <div class="flex items-center space-x-6">

        @include('partials.notification-dropdown')

        <!-- Avatar -->
        <a
    href="{{ route('profil') }}"
    class="block"
>
    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">

        @if($profilePhotoUrl)
            <img
                src="{{ $profilePhotoUrl }}"
                class="block h-full w-full object-cover object-center"
                alt="Foto profil {{ $displayName }}"
            >
        @else
            <span class="flex h-full w-full items-center justify-center">{{ $initials }}</span>
        @endif

    </div>
</a>

    </div>

</header>
