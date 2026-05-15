<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BuatProjekController extends Controller
{
    public function index()
    {
        return view('BuatProjek');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'masalah' => ['required', 'string'],
            'deskripsi' => ['required', 'string'],

            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
        ]);

        try {
            $description = trim($request->deskripsi);
            $description .= "\n\n--- Masalah utama ---\n".trim($request->masalah);

            if ($request->hasFile('lampiran')) {
                $files = $request->file('lampiran');
                if (! empty($files)) {
                    $path = $files[0]->store('project_logos', 'public');
                    $description .= "\n\n[Lampiran: ".Storage::disk('public')->url($path).']';
                }
            }

            /*
            Skema projects di DB: name, description, status, start_date, end_date, created_by (+ timestamps).
            Kolom end_date pada migrasi awal NOT NULL — isi default jangka proyek.
            */
            $startDate = now()->toDateString();
            $endDate = now()->addMonths(6)->toDateString();

            Project::create([
                'name' => $request->judul,
                'description' => $description,
                'status' => 'draft',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('projek-saya')
                ->with('success', 'Projek berhasil dibuat');
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors([
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Gagal menyimpan proyek. Periksa koneksi database dan struktur tabel.',
            ])->withInput();
        }
    }
}
