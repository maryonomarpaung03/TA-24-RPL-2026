<?php

namespace App\Http\Controllers;

use App\Support\ProjectCatalog;
use Illuminate\Http\Request;

class PelaksanaanController extends Controller
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

        // Data Kanban Board
        $kanban = [
            'todo' => [
                ['name' => 'Analisis Kebutuhan Sistem Absensi QR Code', 'creator' => 'NT', 'level' => 'Sulit'],
                ['name' => 'Implementasi fitur profil', 'creator' => 'RH', 'level' => 'Sedang'],
            ],
            'doing' => [
                ['name' => 'Desain UI/UX Halaman Scan QR', 'creator' => 'NT', 'level' => 'Mudah'],
            ],
            'done' => [
                ['name' => 'Implementasi Fitur Scan QR Code', 'creator' => 'DS', 'level' => 'Sedang'],
            ]
        ];

        return view('Pelaksanaan', compact('user', 'namaProjek', 'id', 'kanban'));
    }
}