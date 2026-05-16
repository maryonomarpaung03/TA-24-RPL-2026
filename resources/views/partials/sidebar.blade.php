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
    class="bg-white shadow-md h-screen sticky top-0 transition-all duration-300 flex flex-col"
>

    <!-- Logo + User Initial -->
    <div class="p-6 text-center">
        <a href="{{ auth()->user()->role === 'lecturer' ? route('dosen.dashboard') : route('dashboard') }}"
           class="inline-flex items-center justify-center flex-col gap-1">

            <!-- Avatar -->
            <div class="flex items-center justify-center mx-auto h-12 w-12 rounded-3xl bg-blue-600 text-white text-xl font-bold">
                {{ $initials }}
            </div>

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
    <nav class="flex-1 px-4 space-y-2">

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

        @else
        {{-- Menu mahasiswa --}}
        @include('partials.student-class-join')

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 p-3 rounded-xl transition
           {{ Request::routeIs('dashboard') || Request::routeIs('home')
                ? 'bg-blue-100 text-blue-700'
                : 'text-gray-600 hover:bg-gray-100' }}">
            <i class="fas fa-th-large w-6 text-center"></i>
            <span x-show="sidebarOpen" class="font-semibold">Dashboard</span>
        </a>

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
            $pjblUnlocked = $selected_project['can_access_pjbl'] ?? false;
            $wfIdentification = Request::routeIs('problem-identification');

            $wfDecomposition =
                Request::routeIs('dekomposisi');

            $wfPlanning =
                Request::routeIs('penyusunan')
                || Request::routeIs('tambah-tugas');

            $wfExecution =
                Request::routeIs('pelaksanaan')
                || Request::routeIs('waktu-progres');

            $wfAssessment =
                Request::routeIs('penilaian-individu')
                || Request::routeIs('penilaian-kelompok')
                || Request::routeIs('penilaian-dosen-status')
                || Request::routeIs('nilai-dari-dosen');

            $wfChat =
                Request::routeIs('project-chat');

            $wfActive =
                'flex items-center gap-3 rounded-3xl border border-blue-200 bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-100 transition';

            $wfIdle =
                'flex items-center gap-3 rounded-3xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition';
        @endphp

        <!-- Selected Project -->
        <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-4">

            <p
                x-show="sidebarOpen"
                class="text-[10px] uppercase tracking-[0.25em] text-slate-400 font-semibold mb-3"
            >
                Selected Project
            </p>

            <div class="space-y-3">

                <div class="rounded-3xl bg-white p-3 shadow-sm">
                    <p
                        x-show="sidebarOpen"
                        class="text-sm font-semibold text-slate-900"
                    >
                        {{ $selected_project['name'] }}
                    </p>

                    <p
                        x-show="sidebarOpen"
                        class="text-[10px] text-slate-500 mt-1 line-clamp-2"
                    >
                        @if($pjblUnlocked)
                            {{ $selected_project['description'] }}
                        @elseif($selected_project['is_under_review'] ?? false)
                            In Review — menunggu dosen
                        @elseif($selected_project['is_draft'] ?? false)
                            Draft — belum diajukan
                        @else
                            PjBL belum tersedia
                        @endif
                    </p>
                </div>

                @if(!$pjblUnlocked)
                <a href="{{ route('problem-identification', $selected_project['id']) }}"
                   class="{{ $wfIdentification ? $wfActive : $wfIdle }}">
                    <i class="fas fa-hourglass-half text-lg w-5"></i>
                    <span x-show="sidebarOpen">Project Status</span>
                </a>
                @else
                <a href="{{ route('problem-identification', $selected_project['id']) }}"
                   class="{{ $wfIdentification ? $wfActive : $wfIdle }}">

                    <i class="fas fa-search text-lg w-5"></i>
                    <span x-show="sidebarOpen">
                        Problem Identification
                    </span>
                </a>

                <a href="{{ route('dekomposisi', $selected_project['id']) }}"
                   class="{{ $wfDecomposition ? $wfActive : $wfIdle }}">

                    <i class="fas fa-project-diagram text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Problem Decomposition
                    </span>
                </a>

                <a href="{{ route('penyusunan', $selected_project['id']) }}"
                   class="{{ $wfPlanning ? $wfActive : $wfIdle }}">

                    <i class="fas fa-tasks text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Project Planning
                    </span>
                </a>

                <a href="{{ route('pelaksanaan', $selected_project['id']) }}"
                   class="{{ $wfExecution ? $wfActive : $wfIdle }}">

                    <i class="fas fa-play text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Execution & Evaluation
                    </span>
                </a>

                <a href="{{ route('penilaian-individu', $selected_project['id']) }}"
                   class="{{ $wfAssessment ? $wfActive : $wfIdle }}">

                    <i class="fas fa-clipboard-check text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Assessment & Reflection
                    </span>
                </a>

                <a href="{{ route('project-chat', $selected_project['id']) }}"
                   class="{{ $wfChat ? $wfActive : $wfIdle }}">

                    <i class="fas fa-comments text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Project Chat
                    </span>
                </a>
                @endif

                <a href="{{ route('my-project') }}"
                   class="mt-3 inline-flex w-full items-center justify-center rounded-3xl border border-slate-200 bg-blue-600 px-3 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">

                    <i class="fas fa-exchange-alt text-lg w-5"></i>

                    <span x-show="sidebarOpen">
                        Change Project
                    </span>
                </a>

            </div>
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