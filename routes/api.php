<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProjekSayaController;
use App\Http\Controllers\BuatProjekController;
use App\Http\Controllers\WaktuProgresController;
use App\Http\Controllers\PelaksanaanController;
use App\Http\Controllers\NilaiKelompokController;
use App\Http\Controllers\NilaiIndividuController;
use App\Http\Controllers\BelumDosenNilaiController;
use App\Http\Controllers\NilaiDariDosenController;
use App\Http\Controllers\ProjectChatController;


Route::post('/apilogin', [AuthController::class, 'login'])
    ->name('api-login');

Route::middleware('api.token:student')->group(function () {

    Route::get('/get-projek-api', [BuatProjekController::class, 'getProjectsByEmail'])
        ->name('get-projek-api');

   Route::post('/simpan-projek-api', 
        [BuatProjekController::class, 'storeAPI']
    )->name('simpan-projek-api');

    Route::put(
    '/projek/update-api',
    [BuatProjekController::class, 'updateAPI']
);
});
Route::middleware('api.token:lecturer')->group(function () {

    Route::get('/dosen/persetujuan-proyek-api', 
        [\App\Http\Controllers\DosenApprovalController::class, 'indexApi']
    )->name('dosen.persetujuan-api');

    Route::post(
    '/dosen/persetujuan-proyek/approve-api',
    [\App\Http\Controllers\DosenApprovalController::class, 'approveApi']
)->name('dosen.persetujuan.approve-api');
});