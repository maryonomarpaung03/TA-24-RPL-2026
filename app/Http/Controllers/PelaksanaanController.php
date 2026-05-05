<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PelaksanaanController extends Controller
{
    public function index($id)
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 1
        ];

        // Simulasi data judul projek
        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
        ];

        $namaProjek = $projekList[$id] ?? 'Projek Tidak Ditemukan';

        // Data Kanban Board
        $kanban = [
            'todo' => [
                ['name' => 'Analisis Kebutuhan Sistem Absensi QR Code', 'creator' => 'NT', 'level' => 'Sulit'],
                ['name' => 'Implementasi fitur profil', 'creator' => 'RH', 'level' => 'Sedang'],
            ],
            'doing' => [
                ['name' => 'Desain UI/UX Halaman Scan QR', 'creator' => 'NT', 'level' => 'Mudah'],
            ],
            'done' => [
                ['name' => 'Implementasi Fitur Scan QR Code', 'creator' => 'DS', 'level' => 'Sedang'],
            ]
        ];

        return view('Pelaksanaan', compact('user', 'namaProjek', 'id', 'kanban'));
    }
}