<header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">
    <div>
        <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">selamat datang,</p>
        <h3 class="font-bold text-gray-800 text-sm">{{ $user['name'] ?? 'User' }}</h3>
    </div>
    <div class="flex items-center space-x-6">
        <a href="{{ route('notifikasi') }}" class="relative p-2">
            <i class="fas fa-bell text-gray-300 text-2xl"></i>
            @if(($user['notif_count'] ?? 0) > 0)
                <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] rounded-full h-5 w-5 flex items-center justify-center border-2 border-white font-bold">
                    {{ $user['notif_count'] }}
                </span>
            @endif
        </a>
        <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm">
            {{ $user['initials'] ?? 'U' }}
        </div>
    </div>
</header>
