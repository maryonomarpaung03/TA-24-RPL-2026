@extends('layouts.guest')

@section('title', 'Masuk - DELPRO')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-10 bg-slate-50">

    <div class="w-full max-w-sm rounded-3xl bg-white p-8 shadow-sm border border-slate-200">

        <!-- Logo -->
        <div class="mb-8 text-center">
            <p class="text-xs font-bold uppercase tracking-widest text-blue-600">
                DELPRO
            </p>

            <h1 class="mt-2 text-2xl font-bold text-slate-900">
                Masuk ke akun
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Gunakan email dan kata sandi Anda
            </p>
        </div>

        @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700 border border-green-200">
        {{ session('success') }}
    </div>
@endif

        <!-- Error -->
        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 border border-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Form Login -->
        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="space-y-4"
        >
            @csrf

            <!-- Email -->
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                    placeholder="Masukkan email"
                >
            </div>

            <!-- Password -->
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Kata sandi
                </label>

                <input
                    type="password"
                    name="password"
                    required
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
                    placeholder="Masukkan kata sandi"
                >
            </div>

            <!-- Remember -->
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >
                Ingat saya
            </label>

            <!-- Button Login -->
            <button
                type="submit"
                class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-700 transition"
            >
                Masuk
            </button>

        </form>

        <!-- Register Link -->
        <div class="mt-6 space-y-2 text-center text-sm text-slate-600">
            <p>
                Mahasiswa:
                <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Daftar akun</a>
            </p>
            <p>
                Dosen:
                <a href="{{ route('register.dosen') }}" class="font-semibold text-slate-800 hover:text-slate-900">Daftar akun dosen</a>
            </p>
        </div>

    </div>
</div>
@endsection