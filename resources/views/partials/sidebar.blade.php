@php
    $u = auth()->user();
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

<aside
    :class="sidebarOpen ? 'w-64' : 'w-20'"
    class="bg-white shadow-md h-screen sticky top-0 transition-all duration-300 flex flex-col overflow-hidden"
>

    <!-- Logo + User Initial -->
    <div class="p-4 text-center">
        <a href="{{ auth()->user()->role === 'lecturer' ? route('dosen.dashboard') : route('my-project') }}"
           class="inline-flex items-center justify-center flex-col gap-1">

            

            <h1 class="text-xl font-bold text-blue-600">
                DELPRO
            </h1>

            <p
                x-show="sidebarOpen"
                class="text-gray-400 text-[10px] uppercase font-bold"
            >
                Academic Collaboration
            </p>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 space-y-2 overflow-y-auto overflow-x-hidden min-h-0 pb-6">

        @if(auth()->user()->role === 'lecturer')
        {{-- Menu dosen: Kelas (+), Dashboard, Approval Project --}}
        @include('partials.dosen-class-menu')

        <a href="{{ route('dosen.dashboard') }}"
           class="flex items-center gap-3 p-3 rounded-xl transition
           {{ Request::routeIs('dosen.dashboard')
                ? 'bg-blue-100 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-th-large w-6 text-center"></i>
            <span x-show="sidebarOpen" class="font-semibold">Dashboard</span>
        </a>

        <a href="{{ route('dosen.persetujuan') }}"
           class="flex items-center gap-3 p-3 rounded-xl transition
           {{ Request::routeIs('dosen.persetujuan*')
                ? 'bg-blue-100 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-user-check w-6 text-center"></i>
            <span x-show="sidebarOpen" class="font-semibold">Approval Project</span>
        </a>

        <a href="{{ route('dosen.proyek-mahasiswa') }}"
           class="flex items-center gap-3 p-3 rounded-xl transition
           {{ Request::routeIs('dosen.proyek-mahasiswa*')
                ? 'bg-blue-100 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-graduation-cap w-6 text-center"></i>
            <span x-show="sidebarOpen" class="font-semibold">Proyek Mahasiswa</span>
        </a>

        @else
        {{-- Menu mahasiswa --}}
        @include('partials.student-class-join')

        <!-- Projects -->
        <a href="{{ route('my-project') }}"
           class="flex items-center gap-3 p-3 rounded-xl transition
           {{ Request::routeIs('my-project')
                ? 'bg-blue-100 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100' }}">

            <i class="fas fa-project-diagram w-6 text-center"></i>

            <span
                x-show="sidebarOpen"
                class="font-semibold"
            >
                Projects
            </span>
        </a>

        @if(!empty($selected_project))
        @php
            $statusLabel = $selected_project['status_label'] ?? 'Draft';
            $statusColor = match($selected_project['status'] ?? 'draft') {
                'active'             => 'bg-green-100 text-green-700',
                'pending_approval',
                'pending_revision'   => 'bg-amber-100 text-amber-700',
                'completed'          => 'bg-blue-100 text-blue-700',
                'rejected'           => 'bg-red-100 text-red-700',
                default              => 'bg-slate-100 text-slate-600',
            };
        @endphp

        <!-- Selected Project (minimal) -->
        <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <p x-show="sidebarOpen"
               class="text-[10px] uppercase tracking-[0.25em] text-slate-400 font-semibold mb-2">
                Selected Project
            </p>

            <div x-show="sidebarOpen" class="rounded-2xl bg-white px-3 py-2.5 shadow-sm mb-3">
                <p class="text-sm font-semibold text-slate-900 truncate">
                    {{ $selected_project['name'] }}
                </p>
                <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusColor }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <a href="{{ route('my-project') }}"
               class="inline-flex w-full items-center justify-center gap-2 rounded-3xl border border-slate-200 bg-blue-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                <i class="fas fa-exchange-alt"></i>
                <span x-show="sidebarOpen">Change Project</span>
            </a>
        </div>
        @endif
        @endif
    </nav>

    @if (auth()->user()->role !== 'lecturer' && !Request::routeIs('my-project'))
        <!-- Create Project -->
        <div class="px-4 py-4">
            <a href="{{ route('buat-projek') }}"
               class="block rounded-3xl bg-blue-600 px-4 py-3 text-center text-white font-semibold shadow-md hover:bg-blue-700 transition">
                + New Project
            </a>
        </div>
    @endif

    <!-- Bottom Menu -->
    <div class="mt-auto px-4 pb-5 space-y-2">

        <a href="{{ route('settings') }}"
           class="flex items-center gap-3 rounded-3xl px-4 py-3 transition
           {{ Request::routeIs('settings*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">

            <i class="fas fa-cog w-5 text-center"></i>

            <span
                x-show="sidebarOpen"
                class="font-semibold"
            >
                Settings
            </span>
        </a>

        <form action="{{ route('logout') }}"
              method="POST"
              class="flex">
            @csrf

            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-3xl px-4 py-3 text-left text-gray-600 hover:bg-gray-100 transition">

                <i class="fas fa-sign-out-alt w-5 text-center"></i>

                <span
                    x-show="sidebarOpen"
                    class="font-semibold"
                >
                    Logout
                </span>
            </button>
        </form>

    </div>
</aside>