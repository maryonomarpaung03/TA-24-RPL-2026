<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectCatalog;

class PelaksanaanController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('projek-saya')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return view('Pelaksanaan', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'kanban' => PjblContext::emptyKanban(),
            'teamInitials' => PjblContext::memberInitials((int) $id),
        ]);
    }
}
