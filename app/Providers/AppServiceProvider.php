<?php

namespace App\Providers;

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

            $projectLibrary = [
                1 => [
                    'id' => 1,
                    'name' => 'Aplikasi Absensi Online Berbasis QR Code',
                    'description' => 'Pantau milestone, tugas, dan evaluasi proyek yang sedang berjalan.',
                ],
                2 => [
                    'id' => 2,
                    'name' => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
                    'description' => 'Lihat menu khusus proyek dan navigasi fitur ketika proyek ini dipilih.',
                ],
            ];

            $projectId = $request->query('project_id') ?: $request->route('id');
            $selected_project = null;

            if ($projectId && isset($projectLibrary[$projectId])) {
                $selected_project = $projectLibrary[$projectId];
            }

            $view->with('selected_project', $selected_project);
        });
    }
}
