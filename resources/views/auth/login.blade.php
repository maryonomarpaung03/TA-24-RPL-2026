@extends('layouts.guest')

@section('title', 'Masuk - DELPRO')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/80">
        <div class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600">DELPRO</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Masuk ke akun</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan email dan kata sandi Anda.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="password" class="mb-1 block text-xs font-medium text-slate-600">Kata sandi</label>
                <input id="password" name="password" type="password" required
                       autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-blue-500/0 transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500/30">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Daftar</a>
        </p>
    </div>
</div>
@endsection
