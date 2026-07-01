@if(session('success'))
    <div class="mb-4 rounded bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        {{ session('error') }}
    </div>
@endif
@if(session('info'))
    <div class="mb-4 rounded bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm">
        {{ session('info') }}
    </div>
@endif
