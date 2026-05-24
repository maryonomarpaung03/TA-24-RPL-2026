<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $email = $this->userEmail();
        $filter = $request->query('filter', 'all');

        $query = DB::table('project_notifications')
            ->leftJoin('projects', 'projects.id', '=', 'project_notifications.project_id')
            ->where('project_notifications.recipient_email', $email)
            ->orderByDesc('project_notifications.created_at')
            ->select('project_notifications.*', 'projects.name as project_name');

        if ($filter === 'unread') {
            $query->whereNull('project_notifications.read_at');
        }

        $notifications = $query->limit(100)->get();
        $unreadCount = NotificationPresenter::unreadCount($email);

        return view('Notifikasi', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
            'role' => Auth::user()->role,
        ]);
    }

    public function open(int $id): RedirectResponse
    {
        $email = $this->userEmail();

        $note = DB::table('project_notifications')
            ->where('id', $id)
            ->where('recipient_email', $email)
            ->first();

        if (! $note) {
            return redirect()->route('notifikasi')->with('error', 'Notifikasi tidak ditemukan.');
        }

        NotificationPresenter::markRead($id, $email);

        $url = NotificationPresenter::actionUrl($note, Auth::user()->role);

        return redirect($url ?? route('notifikasi'));
    }

    public function markAllRead(): RedirectResponse
    {
        NotificationPresenter::markAllRead($this->userEmail());

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function userEmail(): string
    {
        return strtolower(trim((string) Auth::user()->email));
    }
}
