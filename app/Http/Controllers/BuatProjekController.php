<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
class BuatProjekController extends Controller
{
    public function index()
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 3
        ];

        return view('BuatProjek', compact('user'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'judul' => 'required',
            'masalah' => 'required',
            'deskripsi' => 'required',
        ]);

        // Di sini nantinya tempat logika menyimpan ke database (Model Project::create)

        // Redirect ke Projek Saya dengan pesan sukses untuk Pop-up
        return redirect()->route('projek-saya')->with('success', 'Projek berhasil dibuat');
    }
}