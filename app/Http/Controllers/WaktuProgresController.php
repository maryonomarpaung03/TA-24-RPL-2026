<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class WaktuProgresController extends Controller
{
    public function index(Request $request, $id)
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

        // 6. Logika Bulan dan Tanggal
        $selectedMonth = $request->get('month', date('n')); // default bulan sekarang
        $selectedYear = date('Y');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $monthName = Carbon::create($selectedYear, $selectedMonth)->translatedFormat('F');

        // 7. Data Tugas (Simulasi data dari halaman penyusunan)
        // Tanggal disesuaikan agar masuk dalam rentang visualisasi
        $tasks = [
            ['name' => 'Requirement Gathering', 'start' => 11, 'end' => 13, 'color' => 'bg-orange-400'],
            ['name' => 'Penyusunan Perencanaan Projek', 'start' => 12, 'end' => 17, 'color' => 'bg-orange-500'],
            ['name' => 'Membuat Fitur Login', 'start' => 24, 'end' => 27, 'color' => 'bg-orange-600'],
            ['name' => 'Membuat Dokumen Bab 1 Rumusan Masalah', 'start' => 17, 'end' => 30, 'color' => 'bg-orange-400'],
        ];

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('WaktuProgres', compact('user', 'namaProjek', 'id', 'daysInMonth', 'monthName', 'tasks', 'months', 'selectedMonth'));
    }
}