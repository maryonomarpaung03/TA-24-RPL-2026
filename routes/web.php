<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjekSayaController;
use App\Http\Controllers\BuatProjekController;
use App\Http\Controllers\WaktuProgresController;
use App\Http\Controllers\PelaksanaanController;
use App\Http\Controllers\NilaiKelompokController;
use App\Http\Controllers\NilaiIndividuController;
use App\Http\Controllers\BelumDosenNilaiController;
use App\Http\Controllers\NilaiDariDosenController;
use App\Http\Controllers\ProjectChatController;

/*
|--------------------------------------------------------------------------
| Login (tamu)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Aplikasi (wajib login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home & Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Project Management
    |--------------------------------------------------------------------------
    */

    Route::get('/projek-saya', [ProjekSayaController::class, 'index'])
        ->name('projek-saya');

    Route::get('/buat-projek', [BuatProjekController::class, 'index'])
        ->name('buat-projek');

    Route::post('/simpan-projek', [BuatProjekController::class, 'store'])
        ->name('simpan-projek');

    /*
    |--------------------------------------------------------------------------
    | Project Detail
    |--------------------------------------------------------------------------
    */

    Route::prefix('projek/{id}')
        ->group(function () {

            Route::get(
                '/dekomposisi',
                [\App\Http\Controllers\DekomposisiController::class, 'index']
            )->name('dekomposisi');

            Route::post(
                '/dekomposisi/sync',
                [\App\Http\Controllers\DekomposisiController::class, 'sync']
            )->name('dekomposisi.sync');

            /*
            |--------------------------------------------------------------------------
            | Penyusunan
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/penyusunan',
                [\App\Http\Controllers\PenyusunanController::class, 'index']
            )->name('penyusunan');

            Route::post(
                '/penyusunan/tambah-tugas',
                [\App\Http\Controllers\PenyusunanController::class, 'tambahTugas']
            )->name('penyusunan.tambah-tugas');

            Route::post(
                '/penyusunan/edit-tugas',
                [\App\Http\Controllers\PenyusunanController::class, 'editTugas']
            )->name('penyusunan.edit-tugas');

            Route::post(
                '/penyusunan/hapus-tugas',
                [\App\Http\Controllers\PenyusunanController::class, 'hapusTugas']
            )->name('penyusunan.hapus-tugas');

            Route::post(
                '/penyusunan/komentar-tugas',
                [\App\Http\Controllers\PenyusunanController::class, 'komentarTugas']
            )->name('penyusunan.komentar-tugas');

            Route::get(
                '/waktu-progres',
                [WaktuProgresController::class, 'index']
            )->name('waktu-progres');

            Route::get(
                '/pelaksanaan',
                [PelaksanaanController::class, 'index']
            )->name('pelaksanaan');

            Route::get(
                '/penilaian-kelompok',
                [NilaiKelompokController::class, 'index']
            )->name('penilaian-kelompok');

            Route::get(
                '/penilaian-individu',
                [NilaiIndividuController::class, 'index']
            )->name('penilaian-individu');

            Route::get(
                '/penilaian-dosen-status',
                [BelumDosenNilaiController::class, 'index']
            )->name('penilaian-dosen-status');

            Route::get(
                '/nilai-dari-dosen',
                [NilaiDariDosenController::class, 'index']
            )->name('nilai-dari-dosen');

            Route::get(
                '/chat',
                [ProjectChatController::class, 'index']
            )->name('project-chat');

            Route::post(
                '/chat',
                [ProjectChatController::class, 'send']
            )->name('project-chat.send');
        });

    /*
    |--------------------------------------------------------------------------
    | Other Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/notifikasi', function () {
        return 'Notifikasi';
    })->name('notifikasi');

    Route::get('/profil', function () {
        return 'Profil';
    })->name('profil');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
