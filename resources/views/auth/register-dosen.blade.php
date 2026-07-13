@extends('layouts.guest')

@section('title', 'Daftar Dosen - PjBL')

@php
    $inputClass = 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20';
    $passwordClass = $inputClass . ' pr-11';
@endphp

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-4xl rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/80">
        <div class="mb-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600">PjBL</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Daftar Akun Dosen</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan NIDN untuk pendaftaran dosen pengampu.</p>
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

        <form method="post" action="{{ route('register.dosen.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                {{-- Baris 1 --}}
                <div>
                    <label for="full_name" class="sr-only">Nama lengkap</label>
                    <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autofocus
                           autocomplete="name" placeholder="Nama Lengkap" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="nidn" class="sr-only">NIDN</label>
                    <input id="nidn" name="nidn" type="text" value="{{ old('nidn') }}" required
                           inputmode="numeric" placeholder="NIDN" class="{{ $inputClass }}">
                </div>

                {{-- Baris 2 --}}
                <div>
                    <label for="phone" class="sr-only">No. HP / WA</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                           autocomplete="tel" placeholder="No. HP/ WA" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           autocomplete="email" placeholder="Email" class="{{ $inputClass }}">
                </div>

                {{-- Baris 3 --}}
                <div>
                    <label for="birth_place_date" class="sr-only">Tanggal lahir</label>
                    <input id="birth_place_date" name="birth_place_date" type="date" value="{{ old('birth_place_date') }}" required
                           class="{{ $inputClass }} text-slate-700">
                </div>
                <div>
                    <label for="gender" class="sr-only">Jenis kelamin</label>
                    <select id="gender" name="gender" required
                            class="{{ $inputClass }} appearance-none bg-white text-slate-700">
                        <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Jenis Kelamin</option>
                        <option value="Laki-laki" @selected(old('gender') === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('gender') === 'Perempuan')>Perempuan</option>
                    </select>
                </div>

                {{-- Baris 4 --}}
                <div class="sm:col-span-2">
                    <label for="address" class="sr-only">Alamat</label>
                    <input id="address" name="address" type="text" value="{{ old('address') }}" required
                           placeholder="Alamat" class="{{ $inputClass }}">
                </div>

                {{-- Baris 5 --}}
                <div class="relative">
                    <label for="password" class="sr-only">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           placeholder="Password" class="{{ $passwordClass }}">
                    <button type="button" data-toggle-password="password" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            aria-label="Tampilkan kata sandi">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 1 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                <div class="relative">
                    <label for="password_confirmation" class="sr-only">Konfirmasi password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           autocomplete="new-password" placeholder="Konfirmasi Password"
                           class="{{ $passwordClass }}">
                    <button type="button" data-toggle-password="password_confirmation" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            aria-label="Tampilkan konfirmasi kata sandi">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 1 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full rounded-full bg-slate-900 py-3.5 text-base font-bold text-white shadow-sm transition hover:bg-slate-800">
                Daftar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Mahasiswa?
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Daftar di sini</a>
            · Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Masuk</a>
        </p>
    </div>
</div>

<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
</script>
@endsection
