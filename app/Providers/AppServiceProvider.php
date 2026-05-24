<?php

namespace App\Providers;

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
        });
    }
}
