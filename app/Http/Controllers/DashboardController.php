<?php

namespace App\Http\Controllers;

use App\Support\ProjectCatalog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 3
        ];

        $statistics = [
            'total_projects' => 4,
            'projects_in_progress' => 2,
            'completed_projects' => 1,
            'pending_approval' => 1,
        ];

        $pie_chart_data = ['Ongoing' => 1, 'Planning' => 1, 'Completed' => 1];
        $bar_chart_data = ['To Do' => 4, 'In Progress' => 3, 'Done' => 1];

        $ongoing_projects = [
            [
                'name' => 'Aplikasi Absensi Online Berbasis QR Code',
                'deadline' => '17 Juni 2026',
                'progress' => 65,
            ],
            [
                'name' => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
                'deadline' => '20 Juni 2026',
                'progress' => 72,
            ],
        ];

        $all_deadlines = [
            ['task' => 'Membuat fitur Login', 'project' => 'Absensi QR', 'days_left' => 2, 'priority' => 'red'],
            ['task' => 'Desain Database', 'project' => 'Absensi QR', 'days_left' => 5, 'priority' => 'yellow'],
            ['task' => 'Test API', 'project' => 'Rekomendasi Film', 'days_left' => 21, 'priority' => 'gray'],
        ];

        // Filter hanya deadline <= 7 hari
        $deadlines = array_filter($all_deadlines, function($d) {
            return $d['days_left'] <= 7;
        });

        $selected_project = null;
        $projectId = $request->query('project_id');
        $initialEditMode = $request->query('mode') === 'edit';
        if ($projectId !== null && $projectId !== '') {
            $selected_project = ProjectCatalog::find($projectId);
        }

        return view('dashboard', compact('user', 'statistics', 'pie_chart_data', 'bar_chart_data', 'ongoing_projects', 'deadlines', 'selected_project', 'initialEditMode'));
    }
}