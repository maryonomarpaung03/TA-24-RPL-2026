<?php

namespace App\Providers;

use App\Services\FinalizationService;
use App\Services\StageProgressService;
use App\Support\NotificationPresenter;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $request = request();

            $projectId = $request->route('id');
            $selected_project = ProjectCatalog::find($projectId);

            $notifCount = 0;
            $recentNotifications = collect();
            if (auth()->check()) {
                $email = strtolower(trim((string) auth()->user()->email));
                $notifCount = NotificationPresenter::unreadCount($email);
                $recentNotifications = NotificationPresenter::forUser($email, 8);
            }

            $view->with([
                'selected_project' => $selected_project,
                'notif_count' => $notifCount,
                'recent_notifications' => $recentNotifications,
                'loggedUser' => auth()->user(),
            ]);

            // Status waterfall tahapan CT dipakai tab bar, tombol finalisasi, dan panel
            // ringkasan — semuanya di-render dari layout, bukan dari controller tiap tahap.
            // Hanya diisi untuk halaman mahasiswa; halaman dosen mengirim overview-nya
            // sendiri lewat controller, dan composer tidak boleh menimpanya.
            if ($selected_project && ($selected_project['can_access_pjbl'] ?? false)) {
                $projectId = (int) $selected_project['id'];
                $activeStage = StageProgressService::stageForRoute($request->route()?->getName());

                $view->with([
                    'stage_overview' => app(StageProgressService::class)->overview($projectId),
                    'active_stage' => $activeStage,
                ]);

                // Form finalisasi proyek hidup di tahap Assessment & Reflection, yang
                // punya empat halaman dengan controller berbeda-beda — jadi prasyaratnya
                // disiapkan di sini, bukan diduplikasi di tiap controller.
                if ($activeStage === StageProgressService::ASSESSMENT) {
                    $view->with([
                        'finalReadiness' => app(FinalizationService::class)->readiness($projectId),
                    ]);
                }
            }
        });
    }
}
