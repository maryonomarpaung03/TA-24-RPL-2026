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
        $diagramSeed = [
            'nodes' => [
                [
                    'key' => 'root',
                    'title' => 'Ketidakefisienan dan ketidakakuratan sistem absensi',
                    'shape' => 'capsule',
                    'color' => '#dbeafe',
                    'x' => 420,
                    'y' => 250,
                ],
                [
                    'key' => 'n1',
                    'title' => 'Input data absensi masih manual',
                    'shape' => 'rounded',
                    'color' => '#fef3c7',
                    'x' => 120,
                    'y' => 140,
                ],
            ],
            'connections' => [
                ['from' => 'root', 'to' => 'n1'],
            ],
            'comments' => [
                ['author' => 'NT', 'text' => 'Bagian validasi real-time sudah saya tambahkan ya teman-teman.'],
            ],
        ];

        return view('Dekomposisi', compact('user', 'namaProjek', 'id', 'diagramSeed'));
    }
}