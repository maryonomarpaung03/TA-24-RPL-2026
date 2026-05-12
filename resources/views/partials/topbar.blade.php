@php
    /*
    sementara pakai user Maryono (id = 2)
    nanti tinggal ganti ke Auth::id()
    */
    $loggedUser = \Illuminate\Support\Facades\DB::table('users')
        ->where('id', 2)
        ->first();

    /*
    notif sementara
    */
    $notifCount = 1;

    /*
    generate initials
    */
    $initials = 'U';

    if ($loggedUser && !empty($loggedUser->full_name)) {

        $words = explode(
            ' ',
            trim($loggedUser->full_name)
        );

        $initials = strtoupper(
            substr($words[0], 0, 1) .
            (
                isset($words[1])
                ? substr($words[1], 0, 1)
                : ''
            )
        );
    }
@endphp

<header class="bg-white px-8 py-4 flex justify-between items-center border-b border-gray-100 sticky top-0 z-40">

    <!-- Welcome -->
    <div>
        <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">
            selamat datang,
        </p>

        <h3 class="font-bold text-gray-800 text-sm">
            {{ $loggedUser->full_name ?? 'User' }}
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
        <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold shadow-sm text-sm">
            {{ $initials }}
        </div>

    </div>

</header>