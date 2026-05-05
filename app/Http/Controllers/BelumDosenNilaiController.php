<?php

namespace App\Http\Controllers; 
use Illuminate\Http\Request; 
class BelumDosenNilaiController extends Controller 
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

        $namaProjek = $projekList[$id] ?? "Projek Tidak Ditemukan";

        return view('BelumDosenNilai', compact('user', 'namaProjek', 'id'));
    }
}