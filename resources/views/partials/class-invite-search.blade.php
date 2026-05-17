@php
    $people = $people ?? [];
    $fieldName = $fieldName ?? 'invite_emails[]';
    $label = $label ?? 'Undang';
    $hint = $hint ?? '';
    $placeholder = $placeholder ?? 'Nama atau email...';
    $oldValues = $oldValues ?? [];
    $alpineKey = $alpineKey ?? 'invite_' . md5($fieldName);
@endphp

<div x-data="{
    people: @js($people),
    query: '',
    invited: @js($oldValues),
    suggestions() {
        if (!this.query.trim()) return [];
        const q = this.query.trim().toLowerCase();
        return this.people.filter(p =>
            !this.invited.includes(p.email) &&
            (p.name.toLowerCase().includes(q) || p.email.toLowerCase().includes(q) || (p.subtitle && p.subtitle.toLowerCase().includes(q)))
        ).slice(0, 6);
    },
    add(email) {
        if (email && !this.invited.includes(email)) this.invited.push(email);
        this.query = '';
    },
    remove(email) {
        this.invited = this.invited.filter(e => e !== email);
    }
}">
    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">{{ $label }}</label>
    @if($hint)
    <p class="text-[10px] text-gray-400 mb-2">{{ $hint }}</p>
    @endif
    <div class="relative">
        <input type="text" x-model="query" placeholder="{{ $placeholder }}"
               class="w-full rounded border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
        <div x-show="suggestions().length > 0" x-cloak
             class="absolute z-10 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
            <template x-for="item in suggestions()" :key="item.email">
                <button type="button" @click="add(item.email)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0 border-gray-50">
                    <span class="font-semibold text-gray-800" x-text="item.name"></span>
                    <span class="block text-[10px] text-gray-400" x-text="item.subtitle || item.email"></span>
                    <span x-show="item.subtitle" class="block text-[10px] text-gray-300" x-text="item.email"></span>
                </button>
            </template>
        </div>
    </div>
    <template x-for="email in invited" :key="email">
        <input type="hidden" name="{{ $fieldName }}" :value="email">
    </template>
    <ul class="mt-2 space-y-1" x-show="invited.length > 0">
        <template x-for="email in invited" :key="'tag-' + email">
            <li class="flex items-center justify-between rounded bg-slate-100 px-2 py-1 text-xs">
                <span x-text="email"></span>
                <button type="button" @click="remove(email)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </li>
        </template>
    </ul>
</div>
