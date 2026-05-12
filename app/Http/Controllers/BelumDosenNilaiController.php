<?php

namespace App\Http\Controllers;

use App\Support\ProjectCatalog;
use Illuminate\Http\Request;

class BelumDosenNilaiController extends Controller 
{
    public function index($id) 
    {
        $user = [
            'name' => 'Daniati Simatupang', 
            'role' => 'Mahasiswa', 
            'initials' => 'DS', 
            'notif_count' => 1
        ];
        
        $namaProjek = ProjectCatalog::name($id);

        return view('BelumDosenNilai', compact('user', 'namaProjek', 'id'));
    }
}