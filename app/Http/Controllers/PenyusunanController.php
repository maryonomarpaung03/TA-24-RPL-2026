<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PenyusunanController extends Controller
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

        // 7. Simulasi Data Tabel
        $tasks = [
            [
                'no' => 1,
                'judul' => 'Analisis Kebutuhan Sistem Absensi QR Code',
                'deskripsi' => 'Mengidentifikasi kebutuhan sistem seperti fitur scan QR, validasi lokasi, dan manajemen data kehadiran mahasiswa.',
                'mulai' => '2026-04-20',
                'selesai' => '2026-04-22',
                'pj' => 'Daniati',
            ],
            [
                'no' => 2,
                'judul' => 'Desain UI/UX Halaman Scan QR',
                'deskripsi' => 'Mendesain tampilan halaman untuk scan QR Code, termasuk kamera, notifikasi berhasil/gagal, dan user flow.',
                'mulai' => '2026-04-23',
                'selesai' => '2026-04-25',
                'pj' => 'Niko',
            ],
            [
                'no' => 3,
                'judul' => 'Implementasi Fitur Scan QR Code',
                'deskripsi' => 'Mengembangkan fitur scan QR menggunakan library, serta menghubungkannya dengan backend untuk mencatat kehadiran.',
                'mulai' => '2026-04-26',
                'selesai' => '2026-04-30',
                'pj' => 'Rehan',
            ],
        ];

        return view('Penyusunan', compact('user', 'namaProjek', 'tasks', 'id'));
    }
}