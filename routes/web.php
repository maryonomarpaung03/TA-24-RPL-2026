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
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProjekSayaController;
use App\Http\Controllers\BuatProjekController;
use App\Http\Controllers\WaktuProgresController;
use App\Http\Controllers\PelaksanaanController;
use App\Http\Controllers\TaskController;
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
Route::get(
    '/login',
    [LoginController::class, 'create']
)->name('login');

Route::post(
    '/login',
    [LoginController::class, 'store']
)->name('login.store');

Route::post(
    '/logout',
    [LoginController::class, 'destroy']
)->name('logout');


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::get(
    '/login',
    [LoginController::class, 'create']
)->name('login');

Route::post(
    '/login',
    [LoginController::class, 'store']
)->name('login.store');

Route::post(
    '/logout',
    [LoginController::class, 'destroy']
)->name('logout');
/*
|--------------------------------------------------------------------------
| Register
|--------------------------------------------------------------------------
*/

Route::get(
    '/register',
    [RegisterController::class, 'create']
)->name('register');

Route::post(
    '/register',
    [RegisterController::class, 'store']
)->name('register.store');

Route::get(
    '/register/dosen',
    [\App\Http\Controllers\RegisterDosenController::class, 'create']
)->name('register.dosen');

Route::post(
    '/register/dosen',
    [\App\Http\Controllers\RegisterDosenController::class, 'store']
)->name('register.dosen.store');

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home & Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->name('home');

    Route::middleware('auth')
    ->group(function () {

        Route::get('/dashboard',
            [DashboardController::class, 'index']
        )->name('dashboard');

});

Route::get(
    '/problem-identification/{id}',
    [DashboardController::class,
    'problemIdentification']
)->name(
    'problem-identification'
);

    /*
    |--------------------------------------------------------------------------
    | Project Management
    |--------------------------------------------------------------------------
    */

    Route::get('/my-project', [ProjekSayaController::class, 'index'])
        ->name('my-project');

    Route::redirect('/projek-saya', '/my-project');

    Route::get('/buat-projek', [BuatProjekController::class, 'index'])
        ->name('buat-projek');

    Route::post('/simpan-projek', [BuatProjekController::class, 'store'])
        ->name('simpan-projek');

    Route::get('/projek/{id}/edit', [BuatProjekController::class, 'edit'])
        ->name('projek.edit');

    Route::put('/projek/{id}', [BuatProjekController::class, 'update'])
        ->name('projek.update');

    Route::delete('/projek/{id}', [BuatProjekController::class, 'destroy'])
        ->name('projek.destroy');

    Route::post('/projek/{id}/tugas/{task}/komentar', [\App\Http\Controllers\TaskCommentController::class, 'store'])
        ->name('task.komentar');

    Route::get('/dosen/dashboard', [\App\Http\Controllers\DosenDashboardController::class, 'index'])
        ->name('dosen.dashboard');

    Route::get('/dosen/persetujuan-proyek', [\App\Http\Controllers\DosenApprovalController::class, 'index'])
        ->name('dosen.persetujuan');

    Route::get('/dosen/persetujuan-proyek/{id}', [\App\Http\Controllers\DosenApprovalController::class, 'show'])
        ->name('dosen.persetujuan.show');

    Route::post('/dosen/persetujuan-proyek/{id}/approve', [\App\Http\Controllers\DosenApprovalController::class, 'approve'])
        ->name('dosen.persetujuan.approve');

    Route::get('/dosen/proyek-mahasiswa', [\App\Http\Controllers\DosenStudentProjectsController::class, 'index'])
        ->name('dosen.proyek-mahasiswa');

    Route::get('/dosen/proyek-mahasiswa/{id}', [\App\Http\Controllers\DosenStudentProjectsController::class, 'show'])
        ->name('dosen.proyek-mahasiswa.show');

    Route::get('/dosen/proyek/{id}/dekomposisi', [\App\Http\Controllers\DosenDekomposisiController::class, 'show'])
        ->name('dosen.dekomposisi');

    Route::get('/dosen/proyek/{id}/penyusunan', [\App\Http\Controllers\DosenProjectMonitorController::class, 'planning'])
        ->name('dosen.penyusunan');

    Route::get('/dosen/proyek/{id}/pelaksanaan', [\App\Http\Controllers\DosenProjectMonitorController::class, 'execution'])
        ->name('dosen.pelaksanaan');

    Route::post('/dosen/proyek/{id}/pelaksanaan/approval/{approvalId}/approve', [\App\Http\Controllers\DosenProjectMonitorController::class, 'approve'])
        ->name('dosen.pelaksanaan.approve');

    Route::post('/dosen/proyek/{id}/pelaksanaan/approval/{approvalId}/reject', [\App\Http\Controllers\DosenProjectMonitorController::class, 'reject'])
        ->name('dosen.pelaksanaan.reject');

    Route::post('/dosen/proyek/{id}/pelaksanaan/tugas/{taskId}/reviewed', [\App\Http\Controllers\DosenProjectMonitorController::class, 'markReviewed'])
        ->name('dosen.pelaksanaan.tandai-review');

    Route::post('/dosen/proyek/{id}/finalisasi/revisi', [\App\Http\Controllers\DosenPenilaianController::class, 'requestRevision'])
        ->name('dosen.finalisasi.revisi');

    Route::get('/dosen/proyek/{id}/penilaian', [\App\Http\Controllers\DosenPenilaianController::class, 'show'])
        ->name('dosen.penilaian');

    Route::post('/dosen/proyek/{id}/penilaian', [\App\Http\Controllers\DosenPenilaianController::class, 'store'])
        ->name('dosen.penilaian.store');

    Route::post('/dosen/proyek/{id}/penilaian/komposisi', [\App\Http\Controllers\DosenPenilaianController::class, 'addComponent'])
        ->name('dosen.penilaian.komposisi.tambah');

    Route::delete('/dosen/proyek/{id}/penilaian/komposisi', [\App\Http\Controllers\DosenPenilaianController::class, 'deleteComponent'])
        ->name('dosen.penilaian.komposisi.hapus');

    Route::get('/dosen/proyek/{id}/problem-identification', [\App\Http\Controllers\DosenProblemReviewController::class, 'show'])
        ->name('dosen.problem-review');

    Route::post('/dosen/proyek/{id}/problem-identification/{problemId}/review', [\App\Http\Controllers\DosenProblemReviewController::class, 'review'])
        ->name('dosen.problem-review.submit');

    Route::post('/dosen/proyek/{id}/tahapan/{requestId}/setujui', [\App\Http\Controllers\DosenStageController::class, 'approve'])
        ->name('dosen.stage.reopen.approve');

    Route::post('/dosen/proyek/{id}/tahapan/{requestId}/tolak', [\App\Http\Controllers\DosenStageController::class, 'reject'])
        ->name('dosen.stage.reopen.reject');

    // Tahap Problem Identification terkunci begitu difinalisasi: aksi tulis di
    // bawah ini ditolak middleware sampai dosen menyetujui perbaikan.
    Route::middleware('stage.waterfall')->group(function () {

        Route::post('/problem-identification/{id}/store', [\App\Http\Controllers\ProblemIdentificationController::class, 'store'])
            ->name('problem.store');

        Route::post('/problem-identification/{id}/update-problem', [\App\Http\Controllers\ProblemIdentificationController::class, 'updateProblem'])
            ->name('problem.update');

        Route::post('/problem-identification/{id}/delete-problem', [\App\Http\Controllers\ProblemIdentificationController::class, 'deleteProblem'])
            ->name('problem.delete');

        Route::post('/problem-identification/{id}/propose-voting', [\App\Http\Controllers\ProblemIdentificationController::class, 'proposeForVoting'])
            ->name('problem.propose-voting');

        Route::post('/problem-identification/{id}/vote', [\App\Http\Controllers\ProblemIdentificationController::class, 'vote'])
            ->name('problem.vote');

        Route::post('/problem-identification/{id}/comment', [\App\Http\Controllers\ProblemIdentificationController::class, 'comment'])
            ->name('problem.comment');

        Route::post('/problem-identification/{id}/discuss', [\App\Http\Controllers\ProblemIdentificationController::class, 'discuss'])
            ->name('problem.discuss');

        Route::post('/problem-identification/{id}/submit-lecturer', [\App\Http\Controllers\ProblemIdentificationController::class, 'submitToLecturer'])
            ->name('problem.submit-lecturer');

        Route::post('/problem-identification/{id}/resubmit', [\App\Http\Controllers\ProblemIdentificationController::class, 'resubmit'])
            ->name('problem.resubmit');
    });

    /*
    |--------------------------------------------------------------------------
    | Project Detail
    |--------------------------------------------------------------------------
    */

    Route::prefix('projek/{id}')
        ->group(function () {

            Route::get(
                '/problem-identification',
                [DashboardController::class, 'problemIdentification']
            )->name('problem-identification');

            Route::post(
                '/ajukan-dosen',
                [\App\Http\Controllers\ProjectSubmissionController::class, 'submit']
            )->name('projek.submit');

        });

    Route::prefix('projek/{id}')
        ->middleware(['project.pjbl', 'stage.waterfall'])
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Tahapan CT (waterfall)
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/tahapan/finalisasi',
                [\App\Http\Controllers\ProjectStageController::class, 'finalize']
            )->name('stages.finalize');

            Route::post(
                '/tahapan/lanjut',
                [\App\Http\Controllers\ProjectStageController::class, 'advance']
            )->name('stages.advance');

            Route::post(
                '/tahapan/perbaikan',
                [\App\Http\Controllers\ProjectStageController::class, 'requestReopen']
            )->name('stages.reopen');

            Route::get(
                '/dekomposisi',
                [\App\Http\Controllers\DekomposisiController::class, 'index']
            )->name('dekomposisi');

            Route::post(
                '/dekomposisi/sync',
                [\App\Http\Controllers\DekomposisiController::class, 'sync']
            )->name('dekomposisi.sync');

            Route::post(
                '/dekomposisi/kirim',
                [\App\Http\Controllers\DekomposisiController::class, 'submit']
            )->name('dekomposisi.submit');

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
            
            Route::post(
                '/pelaksanaan',
                [PelaksanaanController::class, 'store']
            )->name('boards.store');

            Route::post(
                '/finalisasi',
                [\App\Http\Controllers\FinalisasiController::class, 'submit']
            )->name('finalisasi.submit');

            Route::get(
                '/penilaian-kelompok',
                [NilaiKelompokController::class, 'index']
            )->name('penilaian-kelompok');

            Route::post(
                '/penilaian-kelompok',
                [NilaiKelompokController::class, 'store']
            )->name('penilaian-kelompok.store');

            Route::get(
                '/penilaian-individu',
                [NilaiIndividuController::class, 'index']
            )->name('penilaian-individu');

            // Nilai dari dosen baru bisa dilihat setelah tim mengirim finalisasi proyek.
            Route::middleware('final.submitted')->group(function () {

                Route::get(
                    '/penilaian-dosen-status',
                    [BelumDosenNilaiController::class, 'index']
                )->name('penilaian-dosen-status');

                Route::get(
                    '/nilai-dari-dosen',
                    [NilaiDariDosenController::class, 'index']
                )->name('nilai-dari-dosen');
            });

            Route::get(
                '/chat',
                [ProjectChatController::class, 'index']
            )->name('project-chat');

            Route::post(
                '/chat',
                [ProjectChatController::class, 'send']
            )->name('project-chat.send');

            Route::put(
                '/chat/{message}',
                [ProjectChatController::class, 'updateMessage']
            )->name('project-chat.update');

            Route::delete(
                '/chat/{message}',
                [ProjectChatController::class, 'deleteMessage']
            )->name('project-chat.delete');
        });

    /*
    |--------------------------------------------------------------------------
    | Papan Pelaksanaan (task board) - tanpa prefix projek/{id}
    |--------------------------------------------------------------------------
    */

    Route::post('/boards/{board}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/move', [PelaksanaanController::class, 'moveTask'])->name('tasks.move');
    Route::post('/tasks/{taskId}/update', [PelaksanaanController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{taskId}', [PelaksanaanController::class, 'destroyTask'])->name('tasks.destroy');
        /*


    /*
    |--------------------------------------------------------------------------
    | Other Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/notifikasi', [\App\Http\Controllers\NotifikasiController::class, 'index'])
        ->name('notifikasi');

    Route::get('/notifikasi/{id}/buka', [\App\Http\Controllers\NotifikasiController::class, 'open'])
        ->name('notifikasi.open');

    Route::post('/notifikasi/baca-semua', [\App\Http\Controllers\NotifikasiController::class, 'markAllRead'])
        ->name('notifikasi.read-all');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/kelas-saya', [\App\Http\Controllers\StudentClassController::class, 'index'])->name('classes.mine');
    Route::post('/classes/join', [\App\Http\Controllers\StudentClassController::class, 'join'])->name('classes.join');
    Route::get('/classes/{id}', [\App\Http\Controllers\ClassRoomController::class, 'show'])->name('classes.show');
    Route::post('/classes/{id}/chat', [\App\Http\Controllers\ClassRoomController::class, 'send'])->name('classes.chat.send');
    Route::put('/classes/{id}/chat/{message}', [\App\Http\Controllers\ClassRoomController::class, 'updateMessage'])->name('classes.chat.update');
    Route::delete('/classes/{id}/chat/{message}', [\App\Http\Controllers\ClassRoomController::class, 'deleteMessage'])->name('classes.chat.delete');
    Route::post('/classes/{id}/members', [\App\Http\Controllers\ClassRoomController::class, 'addMember'])->name('classes.members.add');
    Route::delete('/classes/{id}/members/{userId}', [\App\Http\Controllers\ClassRoomController::class, 'removeMember'])->name('classes.members.remove');

    Route::get('/dosen/kelas-saya', [\App\Http\Controllers\LecturerClassController::class, 'index'])->name('dosen.kelas');
    Route::post('/dosen/classes', [\App\Http\Controllers\LecturerClassController::class, 'store'])->name('dosen.classes.store');
    Route::put('/dosen/classes/{id}', [\App\Http\Controllers\LecturerClassController::class, 'update'])->name('dosen.classes.update');
    Route::delete('/dosen/classes/{id}', [\App\Http\Controllers\LecturerClassController::class, 'destroy'])->name('dosen.classes.destroy');
    Route::redirect('/profil', '/settings')->name('profil');
});
