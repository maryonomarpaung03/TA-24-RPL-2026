<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DekomposisiController extends Controller
{
    public function index($id)
    {
        return view('Dekomposisi', [
            'id' => $id,
            'namaProjek' => 'Test Project',
            'user' => [
                'name' => 'Maryono Marpaung',
                'role' => 'Mahasiswa',
                'initials' => 'MM',
                'notif_count' => 1
            ],
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