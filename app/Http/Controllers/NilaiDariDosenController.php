<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;

class NilaiDariDosenController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return view('NilaiDariDosen', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'penilaian' => null,
            'hasEvaluation' => false,
        ]);
    }
}
