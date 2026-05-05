<?php

namespace App\Http\Controllers;

class DekomposisiController extends Controller
{
    public function index($id)
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];
        
        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning'
        ];

        $namaProjek = $projekList[$id] ?? 'Projek Unknown';

        return view('Dekomposisi', compact('user', 'namaProjek', 'id'));
    }
}