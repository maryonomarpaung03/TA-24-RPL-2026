<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
class BuatTugasController extends Controller
{
    public function index($id)
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 1
        ];

        // Simulasi judul proyek dinamis
        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
        ];

        $namaProjek = $projekList[$id] ?? 'Projek Tidak Ditemukan';

        // Simulasi daftar anggota untuk dropdown penanggung jawab
        $members = ['Daniati', 'Niko', 'Rehan', 'Siska'];

        return view('BuatTugas', compact('user', 'namaProjek', 'members', 'id'));
    }

    public function store(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'judul_tugas' => 'required',
            'deskripsi' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'pj' => 'required',
        ]);

        // Logika simpan ke database akan diletakkan di sini

        // Kembali ke halaman penyusunan dengan pop-up sukses
        return redirect()->route('penyusunan', $id)->with('success', 'Tugas berhasil ditambahkan');
    }
}