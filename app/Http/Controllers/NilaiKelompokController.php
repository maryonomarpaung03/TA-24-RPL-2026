<?php

namespace App\Http\Controllers;

class NilaiKelompokController extends Controller
{
    public function index($id)
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];
        $namaProjek = "Aplikasi Absensi Online Berbasis QR Code";
        $anggota = ['Daniati Simatupang', 'Rehan', 'Niko', 'Rolan'];

        return view('NilaiKelompok', compact('user', 'namaProjek', 'anggota', 'id'));
    }
}