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

            $projectId = $request->query('project_id') ?: $request->route('id');
            $selected_project = ProjectCatalog::find($projectId);

            $view->with('selected_project', $selected_project);
        });
    }
}
