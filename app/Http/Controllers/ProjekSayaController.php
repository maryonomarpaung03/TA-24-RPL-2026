<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjekSayaController extends Controller
{
    public function index()
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];
        
        $searchHistory = ['Absensi QR', 'Machine Learning', 'Laravel Dashboard'];

        $projects = [
            [
                'id' => 1,
                'name' => 'Aplikasi Absensi Online Berbasis QR Code',
                'status' => 'on progress',
                'progress' => 65,
                'description' => 'Aplikasi ini memungkinkan pencatatan kehadiran menggunakan QR code...',
                'created_at' => '17/06/2026',
                'member_count' => 4,
                'members' => ['DS', 'TB', 'CH', 'AA']
            ],
            [
                'id' => 2,
                'name' => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
                'status' => 'on progress',
                'progress' => 72,
                'description' => 'Membangun algoritma filtering untuk rekomendasi film...',
                'created_at' => '21/06/2026',
                'member_count' => 3,
                'members' => ['DS', 'RB', 'LW']
            ]
        ];

        return view('ProjekSaya', compact('user', 'projects', 'searchHistory'));
    }
}