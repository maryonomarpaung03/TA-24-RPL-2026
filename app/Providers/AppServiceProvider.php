<?php

namespace App\Providers;

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
            if (auth()->check()) {
                $email = strtolower(trim((string) auth()->user()->email));
                $notifCount = \Illuminate\Support\Facades\DB::table('project_notifications')
                    ->where('recipient_email', $email)
                    ->whereNull('read_at')
                    ->count();
            }

            $view->with([
                'selected_project' => $selected_project,
                'notif_count' => $notifCount,
            ]);
        });
    }
}
