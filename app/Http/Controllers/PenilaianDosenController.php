<?php

namespace App\Http\Controllers; 
use Illuminate\Http\Request; 
class PenilaianDosenController extends Controller 
{
    /**
     * Menampilkan hasil penilaian dari dosen.
     * 
     * @param int $id ID dari projek yang dipilih
     */
    public function index($id) 
    {
        // Data profil mahasiswa untuk bagian header
        $user = [
            'name' => 'Daniati Simatupang', 
            'role' => 'Mahasiswa', 
            'initials' => 'DS', 
            'notif_count' => 1
        ];

        // Simulasi judul projek berdasarkan ID
        $projekList = [
            1 => "Aplikasi Absensi Online Berbasis QR Code",
            2 => "Sistem Rekomendasi Film Menggunakan Machine Learning",
        ];

        $namaProjek = $projekList[$id] ?? "Projek Tidak Ditemukan";

        // Simulasi data hasil penilaian yang diberikan oleh dosen
        $nilaiDosen = [
            'angka' => 95, 
            'catatan' => 'Kerja bagus, implementasi QR Code sangat akurat.'
        ];

        // Mengirim data ke view 'PenilaianDosen.blade.php'
        return view('PenilaianDosen', compact('user', 'namaProjek', 'nilaiDosen', 'id'));
    }
}