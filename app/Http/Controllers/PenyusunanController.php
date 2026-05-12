<?php

namespace App\Http\Controllers;

use App\Support\ProjectCatalog;
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

        $namaProjek = ProjectCatalog::name($id);

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
        $diagramSeed = session('diagram_seed_' . $id, []);
        $topicNodes = collect($diagramSeed['nodes'] ?? [])
            ->filter(fn ($n) => ($n['key'] ?? null) !== 'root')
            ->values()
            ->all();

        $topicTasks = collect($topicNodes)->map(function ($node) use ($user) {
            $title = $node['title'] ?? null;
            if (!$title) {
                return null;
            }
            $createdAt = $node['createdAt'] ?? $node['created_at'] ?? now()->toDateString();
            return [
                'judul' => $title,
                'deskripsi' => '-',
                'mulai' => $createdAt,
                'selesai' => '-',
                'pj' => $user['name'],
            ];
        })->filter()->values()->all();

        $tasks = array_merge($topicTasks, $tasks);
        $tasks = collect($tasks)->values()->map(function ($task, $index) {
            $task['no'] = $index + 1;
            return $task;
        })->all();

        return view('Penyusunan', compact('user', 'namaProjek', 'tasks', 'id'));
    }
}