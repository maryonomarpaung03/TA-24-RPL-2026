<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuatProjekController extends Controller
{
    public function index()
    {
        return view('BuatProjek');
    }

    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'judul' => 'required|string|max:200',
            'masalah' => 'required|string',
            'deskripsi' => 'required|string',

            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,doc,docx',
        ]);

        try {

            // Default logo null
            $logoPath = null;

            // Upload file jika ada
            if ($request->hasFile('lampiran')) {

                $files = $request->file('lampiran');

                if (!empty($files)) {
                    $logoPath = $files[0]->store(
                        'project_logos',
                        'public'
                    );
                }
            }

            /*
            sementara ambil team pertama
            karena team_id wajib
            */
            $team = DB::table('teams')->first();

            if (!$team) {
                return back()->withErrors([
                    'error' => 'Belum ada team pada database.'
                ]);
            }

            // Jika belum ada auth login
            $userId = Auth::id() ?? 2;

            // Simpan project
            Project::create([
                'team_id' => $team->id,
                'created_by' => $userId,
                'title' => $request->judul,
                'description' => $request->deskripsi,
                'problem_definition' => $request->masalah,
                'logo' => $logoPath,
                'status' => 'draft',
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'created_at' => now()
            ]);

            return redirect()
                ->route('projek-saya')
                ->with('success', 'Projek berhasil dibuat');

        } catch (\Exception $e) {

            dd($e->getMessage());

            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}