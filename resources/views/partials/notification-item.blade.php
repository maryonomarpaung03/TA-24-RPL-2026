@php
    $role = $role ?? auth()->user()->role;
    $compact = $compact ?? false;
    $style = \App\Support\NotificationPresenter::styleForType((string) ($note->type ?? ''));
    $isUnread = empty($note->read_at);
    $openUrl = \App\Support\NotificationPresenter::openUrl((int) $note->id);
@endphp

<a href="{{ $openUrl }}"
   class="flex gap-3 px-4 py-3 transition hover:bg-slate-50 {{ $isUnread ? 'bg-blue-50/60' : '' }} {{ $compact ? '' : 'rounded-xl border border-transparent hover:border-slate-100' }}">
    <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-full {{ $style['bg'] }} {{ $style['text'] }}">
        <i class="fas {{ $style['icon'] }} text-sm"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-semibold text-slate-900 leading-snug {{ $isUnread ? '' : 'font-medium text-slate-700' }}">
                {{ $note->title }}
            </p>
            @if($isUnread)
            <span class="shrink-0 mt-1 h-2 w-2 rounded-full bg-blue-500"></span>
            @endif
        </div>
        <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $note->message }}</p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-[10px] text-slate-400">
            @if(!empty($note->project_name))
            <span>{{ $note->project_name }}</span>
            <span>&middot;</span>
            @endif
            <time>{{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}</time>
        </div>
    </div>
</a>
