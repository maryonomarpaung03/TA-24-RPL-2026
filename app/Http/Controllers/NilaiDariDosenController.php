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
            'prodi' => 'D4 Teknologi Rekayasa Perangkat Lunak',
            'dosen' => 'Aruma Nainggolan S.S.T., M.I.M.',
            'komentar' => 'Harus lebih memahami isi implementasi dan berpartisipasi di presentasi',
            'kriteria' => [
                ['label' => 'Pemahaman projek', 'skor' => 80],
                ['label' => 'Keaktifan', 'skor' => 96],
                ['label' => 'Presentasi', 'skor' => 75],
                ['label' => 'Implementasi', 'skor' => 40],
                ['label' => 'Dokumentasi', 'skor' => 97],
            ]
        ];

        return view('NilaiDariDosen', compact('user', 'namaProjek', 'id', 'penilaian'));
    }
}