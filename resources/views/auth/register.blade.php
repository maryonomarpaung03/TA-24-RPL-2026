@extends('layouts.guest')

@section('title', 'Daftar - DELPRO')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/80">
        <div class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600">DELPRO</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Buat akun</h1>
            <p class="mt-1 text-sm text-slate-500">Isi data sesuai yang tersimpan di sistem.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-100">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="full_name" class="mb-1 block text-xs font-medium text-slate-600">Nama lengkap</label>
                <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autofocus
                       autocomplete="name"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="email" class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="email"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="password" class="mb-1 block text-xs font-medium text-slate-600">Kata sandi</label>
                <input id="password" name="password" type="password" required
                       autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-xs font-medium text-slate-600">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Daftar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Masuk</a>
        </p>
    </div>
</div>
@endsection
