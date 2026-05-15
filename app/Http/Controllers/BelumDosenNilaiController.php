<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;

class BelumDosenNilaiController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('projek-saya')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return view('BelumDosenNilai', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
        ]);
    }
}
