<?php

namespace App\Http\Controllers;

use App\Support\ProjectAccess;
use App\Support\ProjectCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DekomposisiController extends Controller
{
    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        $user = Auth::user();
        $displayName = $user?->full_name ?? $user?->name ?? 'User';
        $initials = ProjectAccess::initialsFromName($displayName);

        return view('Dekomposisi', [
            'id' => $id,
            'namaProjek' => $selected['name'],
            'user' => [
                'name' => $displayName,
                'role' => $user?->role ?? 'student',
                'initials' => $initials,
                'notif_count' => 1,
            ],
            'selected_project' => $selected,
            'diagramSeed' => [
                'nodes' => [],
                'connections' => [],
                'comments' => []
            ]
        ]);
    }

    public function sync(Request $request, $id)
    {
        return response()->json([
            'ok' => true
        ]);
    }
}