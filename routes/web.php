<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjekSayaController;
use App\Http\Controllers\BuatProjekController;
use App\Http\Controllers\DekomposisiController;
use App\Http\Controllers\PenyusunanController;
use App\Http\Controllers\WaktuProgresController;
use App\Http\Controllers\PelaksanaanController;
use App\Http\Controllers\NilaiKelompokController;
use App\Http\Controllers\NilaiIndividuController;
use App\Http\Controllers\BelumDosenNilaiController;
use App\Http\Controllers\NilaiDariDosenController;
use App\Http\Controllers\ProjectChatController;

// Dasar & Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Manajemen Projek Utama
Route::get('/projek-saya', [ProjekSayaController::class, 'index'])->name('projek-saya');
Route::get('/buat-projek', [BuatProjekController::class, 'index'])->name('buat-projek');
Route::post('/simpan-projek', [BuatProjekController::class, 'store'])->name('simpan-projek');

// Fitur Detail Projek (ID Dinamis)
Route::prefix('projek/{id}')->group(function () {
    Route::get('/dekomposisi', [DekomposisiController::class, 'index'])->name('dekomposisi');
    Route::post('/dekomposisi/sync', [DekomposisiController::class, 'sync'])->name('dekomposisi.sync');
    Route::get('/penyusunan', [PenyusunanController::class, 'index'])->name('penyusunan');
    Route::get('/waktu-progres', [WaktuProgresController::class, 'index'])->name('waktu-progres');
    Route::get('/pelaksanaan', [PelaksanaanController::class, 'index'])->name('pelaksanaan');
    Route::get('/penilaian-kelompok', [NilaiKelompokController::class, 'index'])->name('penilaian-kelompok');
    Route::get('/penilaian-individu', [NilaiIndividuController::class, 'index'])->name('penilaian-individu');
    Route::get('/penilaian-dosen-status', [BelumDosenNilaiController::class, 'index'])->name('penilaian-dosen-status');
    Route::get('/nilai-dari-dosen', [NilaiDariDosenController::class, 'index'])->name('nilai-dari-dosen');
    Route::get('/chat', [ProjectChatController::class, 'index'])->name('project-chat');
    Route::post('/chat', [ProjectChatController::class, 'send'])->name('project-chat.send');
});

// Lainnya
Route::get('/notifikasi', function() { return "Notifikasi"; })->name('notifikasi');
Route::get('/profil', function() { return "Profil"; })->name('profil');
Route::post('/logout', function() { return "Logout"; })->name('logout');