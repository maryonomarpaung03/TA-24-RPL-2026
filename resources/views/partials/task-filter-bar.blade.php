@php
    // Dipakai di Penyusunan & Pelaksanaan. Butuh state Alpine: fAssignee, fDeadline, myId.
    $filterMembers = $members ?? $users ?? [];
@endphp

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-wrap items-center gap-3">
    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">
        <i class="fas fa-filter mr-1"></i>Filter
    </span>

    <select x-model="fAssignee"
            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-blue-400">
        <option value="all">Semua penanggung jawab</option>
        <option value="mine">★ Tugas Saya</option>
        @foreach($filterMembers as $m)
        <option value="{{ $m->id }}">{{ $m->full_name }}</option>
        @endforeach
    </select>

    <select x-model="fDeadline"
            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-blue-400">
        <option value="all">Semua deadline</option>
        <option value="overdue">Terlewat</option>
        <option value="urgent">Urgent (≤3 hari)</option>
        <option value="soon">Mendekati (≤7 hari)</option>
    </select>

    <button type="button" @click="fAssignee = 'mine'"
            :class="fAssignee === 'mine' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-600 border-blue-200 hover:bg-blue-50'"
            class="rounded-xl border px-3 py-2 text-sm font-semibold transition">
        <i class="fas fa-user mr-1"></i>Tugas Saya
    </button>

    <button type="button" @click="fAssignee = 'all'; fDeadline = 'all'"
            x-show="fAssignee !== 'all' || fDeadline !== 'all'"
            class="ml-auto rounded-xl px-3 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
        <i class="fas fa-times mr-1"></i>Reset filter
    </button>
</div>
