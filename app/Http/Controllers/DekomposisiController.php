<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $defaultSeed = [
            'nodes' => [
                [
                    'key' => 'root',
                    'title' => 'Ketidakefisienan dan ketidakakuratan sistem absensi',
                    'shape' => 'capsule',
                    'color' => '#dbeafe',
                    'x' => 420,
                    'y' => 250,
                    'createdBy' => $user['name'],
                    'createdAt' => now()->toDateString(),
                ],
                [
                    'key' => 'n1',
                    'title' => 'Input data absensi masih manual',
                    'shape' => 'rounded',
                    'color' => '#fef3c7',
                    'x' => 120,
                    'y' => 140,
                    'createdBy' => $user['name'],
                    'createdAt' => now()->toDateString(),
                ],
            ],
            'connections' => [
                ['from' => 'root', 'to' => 'n1'],
            ],
            'comments' => [
                ['author' => 'NT', 'text' => 'Bagian validasi real-time sudah saya tambahkan ya teman-teman.'],
            ],
        ];
        $diagramSeed = session('diagram_seed_' . $id, $defaultSeed);

        return view('Dekomposisi', compact('user', 'namaProjek', 'id', 'diagramSeed'));
    }

    public function sync(Request $request, $id)
    {
        $payload = $request->validate([
            'nodes' => 'required|array',
            'connections' => 'nullable|array',
        ]);

        session(['diagram_seed_' . $id => [
            'nodes' => $payload['nodes'],
            'connections' => $payload['connections'] ?? [],
            'comments' => session('diagram_seed_' . $id . '.comments', []),
        ]]);

        return response()->json(['ok' => true]);
    }
}