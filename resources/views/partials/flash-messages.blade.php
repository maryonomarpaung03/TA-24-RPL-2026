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
{{-- Galat validasi: tanpa ini, form yang ditolak akan kembali tanpa penjelasan. --}}
@if($errors->any())
    <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        <p class="font-bold mb-1">Ada isian yang perlu diperbaiki:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach(collect($errors->all())->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
