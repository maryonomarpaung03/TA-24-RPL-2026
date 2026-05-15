<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotifikasiController extends Controller
{
    public function index()
    {
        $email = strtolower(trim((string) Auth::user()->email));

        $notifications = DB::table('project_notifications')
            ->leftJoin('projects', 'project_notifications.project_id', '=', 'projects.id')
            ->where('project_notifications.recipient_email', $email)
            ->orderByDesc('project_notifications.created_at')
            ->select(
                'project_notifications.*',
                'projects.name as project_name'
            )
            ->limit(50)
            ->get();

        DB::table('project_notifications')
            ->where('recipient_email', $email)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return view('Notifikasi', [
            'notifications' => $notifications,
        ]);
    }
}
