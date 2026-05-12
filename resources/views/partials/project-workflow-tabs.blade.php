@php
    $pid = $selected_project['id'];
    $wfIdentification = Request::routeIs('dashboard') || Request::routeIs('home');
    $wfDecomposition = Request::routeIs('dekomposisi');
    $wfPlanning = Request::routeIs('penyusunan') || Request::routeIs('tambah-tugas');
    $wfExecution = Request::routeIs('pelaksanaan') || Request::routeIs('waktu-progres');
    $wfAssessment = Request::routeIs('penilaian-individu')
        || Request::routeIs('penilaian-kelompok')
        || Request::routeIs('penilaian-dosen-status')
        || Request::routeIs('nilai-dari-dosen');
    $wfChat = Request::routeIs('project-chat');
    $tabActive = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm';
    $tabIdle = 'shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition';
@endphp
<div class="border-b border-slate-200 bg-slate-50/90 px-4 py-3 z-30">
    <div class="flex flex-col gap-3 max-w-[100vw]">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold shrink-0">Selected project</span>
            <span class="text-sm font-semibold text-slate-900 truncate">{{ $selected_project['name'] }}</span>
        </div>
        <nav class="flex gap-2 overflow-x-auto pb-0.5" aria-label="Alur kerja proyek">
            <a href="{{ route('dashboard', ['project_id' => $pid, 'mode' => 'view']) }}" class="{{ $wfIdentification ? $tabActive : $tabIdle }}">
                <i class="fas fa-search text-[11px]"></i>
                Problem Identification
            </a>
            <a href="{{ route('dekomposisi', $pid) }}" class="{{ $wfDecomposition ? $tabActive : $tabIdle }}">
                <i class="fas fa-project-diagram text-[11px]"></i>
                Problem Decomposition
            </a>
            <a href="{{ route('penyusunan', $pid) }}" class="{{ $wfPlanning ? $tabActive : $tabIdle }}">
                <i class="fas fa-tasks text-[11px]"></i>
                Project Planning
            </a>
            <a href="{{ route('pelaksanaan', $pid) }}" class="{{ $wfExecution ? $tabActive : $tabIdle }}">
                <i class="fas fa-play text-[11px]"></i>
                Execution & Evaluation
            </a>
            <a href="{{ route('penilaian-individu', $pid) }}" class="{{ $wfAssessment ? $tabActive : $tabIdle }}">
                <i class="fas fa-clipboard-check text-[11px]"></i>
                Assessment &amp; Reflection
            </a>
            <a href="{{ route('project-chat', $pid) }}" class="{{ $wfChat ? $tabActive : $tabIdle }}">
                <i class="fas fa-comments text-[11px]"></i>
                Project Chat
            </a>
        </nav>
    </div>
</div>
