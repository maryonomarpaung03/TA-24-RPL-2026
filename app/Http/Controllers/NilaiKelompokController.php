<?php

namespace App\Http\Controllers;

class NilaiKelompokController extends Controller
{
    public function index($id)
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];
        
        $groupData = [
            'name' => 'Group 4 - Smart Irrigation System',
            'project' => 'Aplikasi Absensi Online Berbasis QR Code',
            'semester' => 'Semester 5 • Advanced PSL Workshop',
            'members' => [
                ['name' => 'Anggota Atas', 'initial' => 'AA', 'role' => 'Lead']
            ]
        ];

        $milestones = [
            ['phase' => 'Phase 1 Planning', 'status' => 'done'],
            ['phase' => 'Phase 2 Execution', 'status' => 'done'],
            ['phase' => 'Phase 3 Final Report', 'status' => 'in-review'],
        ];

        $ctMetrics = [
            ['label' => 'Abstraction', 'value' => 8.5, 'max' => 10],
            ['label' => 'Computational Logic', 'value' => 9.2, 'max' => 10],
            ['label' => 'Problem Decomposition', 'value' => 7.8, 'max' => 10],
        ];

        $peerReview = [
            ['label' => 'Technical', 'score' => 4.2],
            ['label' => 'Reliability', 'score' => 4.8],
            ['label' => 'Testing', 'score' => 4.5],
            ['label' => 'Conflict Res', 'score' => 3.9],
        ];

        $feedbackText = "Kelompok 4 menunjukkan sinergi yang sangat baik dalam fase desain dan implementasi algoritma. Apresiasi kami kepada persiapan yang matang sebelum submission fase akhir dilaksanakan.";

        $lecturer = [
            'name' => 'Dr. Ahmad Faisal',
            'title' => 'Lecturer & Project Supervisor',
            'feedback' => '"Kelompok 4 memperlihatkan sinergi yang sangat baik dalam fase desain dan implementasi algoritma. Apresiasi kami kepada persiapan yang matang sebelum submission fase akhir dilaksanakan. Saya sangat terkesan dengan dokumentasi integrasi API di fase berkunjung."'
        ];

        return view('NilaiKelompok', compact('user', 'groupData', 'milestones', 'ctMetrics', 'peerReview', 'feedbackText', 'lecturer', 'id'));
    }
}