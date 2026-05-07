<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NilaiDariDosenController extends Controller
{
    public function index($id)
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 1
        ];

        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
        ];

        $namaProjek = $projekList[$id] ?? 'Projek Tidak Ditemukan';

        // 6. Data Penilaian Dosen (Simulasi)
        $penilaian = [
            'mahasiswa' => 'Daniati Simatupang',
            'student_id' => '12251045',
            'prodi' => 'D4 Teknologi Rekayasa Perangkat Lunak',
            'dosen' => 'Dr. Aris Santoso, M.Kom.',
            'team_role' => 'Project Manager',
            'evaluation_date' => 'Oct 24, 2023',
            'komentar' => 'Daniati has demonstrated exceptional leadership skills throughout the Project Manager rotation. Her ability to decompose complex greenhouse environmental variables into computational thinking models was highly impressive. While her implementation skills are strong, focusing more on clean code principles in the back-end architecture would further elevate her work. Documentation remains a benchmark for other students. Overall, an outstanding semester performance.',
            'kriteria' => [
                ['label' => 'Project Understanding', 'skor' => 92],
                ['label' => 'Activity', 'skor' => 88],
                ['label' => 'Presentation', 'skor' => 95],
                ['label' => 'Implementation', 'skor' => 85],
                ['label' => 'Documentation', 'skor' => 90],
            ],
            'skills' => [
                ['label' => 'Problem Decomposition', 'score' => 95],
                ['label' => 'Pattern Recognition', 'score' => 82],
                ['label' => 'Algorithm Design', 'score' => 78],
            ],
            'interactions' => [
                ['label' => 'Commits', 'value' => '142 Commits', 'meta' => 'Last activity 2 hours ago'],
                ['label' => 'Tasks Resolved', 'value' => '45 Tasks Resolved', 'meta' => 'Project Manager role active'],
                ['label' => 'Discussions', 'value' => '28 Discussions', 'meta' => 'High collaborative engagement'],
            ],
        ];

        $nilaiSkor = array_sum(array_column($penilaian['kriteria'], 'skor')) / count($penilaian['kriteria']);
        $penilaian['average'] = round($nilaiSkor, 1);
        if ($nilaiSkor >= 90) {
            $penilaian['grade'] = 'A';
        } elseif ($nilaiSkor >= 85) {
            $penilaian['grade'] = 'A-';
        } elseif ($nilaiSkor >= 80) {
            $penilaian['grade'] = 'B+';
        } else {
            $penilaian['grade'] = 'B';
        }

        return view('NilaiDariDosen', compact('user', 'namaProjek', 'id', 'penilaian'));
    }
}