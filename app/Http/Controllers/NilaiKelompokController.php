<?php

namespace App\Http\Controllers;

use App\Support\PjblContext;
use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;

class NilaiKelompokController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('projek-saya')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $members = ProjectAccess::teamMembersForProject((int) $id);

        return view('NilaiKelompok', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
            'groupData' => [
                'name' => $selected['group_name'] ?? '-',
                'project' => $selected['name'],
                'members' => array_map(fn (array $m) => [
                    'name' => $m['name'],
                    'initial' => $m['initials'],
                    'role' => $m['role'],
                ], $members),
            ],
            'anggota' => array_column($members, 'name'),
        ]);
    }
}
