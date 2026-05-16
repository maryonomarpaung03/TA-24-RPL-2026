<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DosenDashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403, 'Halaman ini hanya untuk dosen.');
        }

        // Data statis sementara — integrasi database menyusul
        $statistics = [
            'total_proyek' => 24,
            'proyek_berjalan' => 12,
            'mahasiswa_kelas' => 156,
            'mata_kuliah' => 4,
        ];

        $pending_approvals = [
            [
                'id' => 1,
                'name' => 'Sistem Manajemen Inventori Lab',
                'creator_name' => 'Ahmad Rizki',
                'course' => 'Rekayasa Perangkat Lunak',
                'submitted_at' => '14 Mei 2026',
            ],
            [
                'id' => 2,
                'name' => 'Aplikasi Monitoring Proyek Kampus',
                'creator_name' => 'Siti Nurhaliza',
                'course' => 'Pemrograman Web',
                'submitted_at' => '13 Mei 2026',
            ],
            [
                'id' => 3,
                'name' => 'Platform Kolaborasi Mahasiswa',
                'creator_name' => 'Budi Santoso',
                'course' => 'Basis Data',
                'submitted_at' => '12 Mei 2026',
            ],
            [
                'id' => 4,
                'name' => 'Chatbot FAQ Akademik',
                'creator_name' => 'Dewi Lestari',
                'course' => 'Kecerdasan Buatan',
                'submitted_at' => '11 Mei 2026',
            ],
            [
                'id' => 5,
                'name' => 'Dashboard Analitik Nilai',
                'creator_name' => 'Eko Prasetyo',
                'course' => 'Visualisasi Data',
                'submitted_at' => '10 Mei 2026',
            ],
        ];

        $problem_voting_notifications = [
            [
                'id' => 101,
                'project_name' => 'Sistem Manajemen Inventori Lab',
                'problem_title' => 'Stok barang lab tidak terpantau real-time',
                'student_group' => 'Kelompok Alpha',
                'votes' => 8,
                'time_ago' => '2 jam yang lalu',
            ],
            [
                'id' => 102,
                'project_name' => 'Aplikasi Monitoring Proyek Kampus',
                'problem_title' => 'Deadline tugas sulit dilacak antar anggota',
                'student_group' => 'Kelompok Beta',
                'votes' => 6,
                'time_ago' => '5 jam yang lalu',
            ],
            [
                'id' => 103,
                'project_name' => 'Platform Kolaborasi Mahasiswa',
                'problem_title' => 'Komunikasi tim tersebar di banyak channel',
                'student_group' => 'Kelompok Gamma',
                'votes' => 7,
                'time_ago' => '1 hari yang lalu',
            ],
            [
                'id' => 104,
                'project_name' => 'Chatbot FAQ Akademik',
                'problem_title' => 'Mahasiswa kesulitan menemukan info akademik',
                'student_group' => 'Kelompok Delta',
                'votes' => 5,
                'time_ago' => '1 hari yang lalu',
            ],
            [
                'id' => 105,
                'project_name' => 'Dashboard Analitik Nilai',
                'problem_title' => 'Rekap nilai tidak terintegrasi per mata kuliah',
                'student_group' => 'Kelompok Epsilon',
                'votes' => 9,
                'time_ago' => '2 hari yang lalu',
            ],
        ];

        $pending_total = 8;
        $notifications_total = 12;

        return view('DosenDashboard', compact(
            'statistics',
            'pending_approvals',
            'problem_voting_notifications',
            'pending_total',
            'notifications_total'
        ));
    }
}
