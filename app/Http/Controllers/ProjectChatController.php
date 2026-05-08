<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectChatController extends Controller
{
    public function index($id)
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];

        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
        ];
        $namaProjek = $projekList[$id] ?? 'Projek Tidak Ditemukan';

        $memberMap = [
            1 => ['Daniati Simatupang', 'Niko Tarigan', 'Rehan Hutabarat'],
            2 => ['Daniati Simatupang', 'Niko Tarigan'],
        ];
        $members = $memberMap[$id] ?? [$user['name']];

        $defaultMessages = [
            ['author' => 'Niko Tarigan', 'text' => 'Aku lanjutkan bagian UI planning ya.', 'time' => '09:10'],
            ['author' => 'Rehan Hutabarat', 'text' => 'Siap, aku bantu backend endpoint.', 'time' => '09:12'],
        ];
        $messages = session('project_chat_' . $id, $defaultMessages);

        return view('ProjectChat', compact('id', 'user', 'namaProjek', 'members', 'messages'));
    }

    public function send(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $messages = session('project_chat_' . $id, []);
        $messages[] = [
            'author' => 'Daniati Simatupang',
            'text' => trim($validated['message']),
            'time' => now()->format('H:i'),
        ];

        session(['project_chat_' . $id => $messages]);

        return redirect()->route('project-chat', $id);
    }
}
