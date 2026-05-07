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
                'status' => 'In Progress',
                'label' => 'In Progress',
                'progress' => 68,
                'description' => 'Eksperimen integrasi Computational Thinking dalam kurikulum SMK melalui proyek nyata pembangunan smart garden berbasis sensor IoT.',
                'created_at' => '17/06/2026',
                'member_count' => 6,
                'members' => ['DS', 'TB', 'CH', '+2'],
                'featured' => true
            ],
            [
                'id' => 2,
                'name' => 'Abstraksi Aljabar Digital',
                'status' => 'Planning',
                'label' => 'Planning',
                'progress' => 15,
                'description' => 'Penerapan konsep abstraksi CT untuk mempermudah pemahaman konsep aljabar dalam visualisasi.',
                'created_at' => '24/06/2026',
                'member_count' => 2,
                'members' => ['AS', 'BK']
            ],
            [
                'id' => 3,
                'name' => 'Visualisasi Algoritma Kota',
                'status' => 'In Progress',
                'label' => 'Active',
                'progress' => 42,
                'description' => 'Proyek kolaborasi teknik sipil dan informatika untuk memvisualisasikan jalur optimal kota.',
                'created_at' => '19/06/2026',
                'member_count' => 4,
                'members' => ['HS', 'JC', '+2']
            ],
            [
                'id' => 4,
                'name' => 'Robotika Berbasis Pola',
                'status' => 'Done',
                'label' => 'Done',
                'progress' => 100,
                'description' => 'Analisis pengenalan pola gerakan motorik pada robot edukasi berbasis CT.',
                'created_at' => '12/06/2026',
                'member_count' => 1,
                'members' => ['RK']
            ]
        ];

        return view('ProjekSaya', compact('user', 'projects', 'searchHistory'));
    }
}