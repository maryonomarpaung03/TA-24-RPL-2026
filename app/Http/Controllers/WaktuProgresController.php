<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class WaktuProgresController extends Controller
{
    public function index(Request $request, $id)
    {
        $user = [
            'name' => 'Daniati Simatupang',
            'role' => 'Mahasiswa',
            'initials' => 'DS',
            'notif_count' => 1,
        ];

        $projekList = [
            1 => 'Aplikasi Absensi Online Berbasis QR Code',
            2 => 'Sistem Rekomendasi Film Menggunakan Machine Learning',
        ];

        $namaProjek = $projekList[$id] ?? 'Projek Tidak Ditemukan';

        $selectedMonth = $request->get('month', 2);
        $selectedYear = 2026;
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $monthName = Carbon::create($selectedYear, $selectedMonth)->translatedFormat('F');

        $tasks = [
            ['name' => 'Literature Review', 'start' => 1, 'end' => 5, 'color' => 'bg-slate-700', 'status' => 'Complete'],
            ['name' => 'CT Framework Analysis', 'start' => 5, 'end' => 15, 'color' => 'bg-orange-500', 'status' => 'In Progress'],
            ['name' => 'Data Collection Phase I', 'start' => 8, 'end' => 13, 'color' => 'bg-blue-500', 'status' => 'In Progress'],
            ['name' => 'Pilot Study Evaluation', 'start' => 15, 'end' => 20, 'color' => 'bg-cyan-500', 'status' => 'Pending'],
            ['name' => 'Prototype Iteration', 'start' => 18, 'end' => 23, 'color' => 'bg-indigo-500', 'status' => 'Pending'],
            ['name' => 'Mid-term Presentation', 'start' => 24, 'end' => 27, 'color' => 'bg-emerald-500', 'status' => 'Upcoming'],
        ];

        $progressMilestone = 70;
        $completedTasks = 14;
        $totalTasks = 20;

        $milestones = [
            ['title' => 'Data Integration Module', 'due' => 'Feb 15, 2026', 'progress' => 85],
            ['title' => 'User Feedback Analysis', 'due' => 'Feb 22, 2026', 'progress' => 40],
            ['title' => 'Academic Documentation', 'due' => 'Mar 01, 2026', 'progress' => 20],
        ];

        $deadlineCard = [
            'title' => 'February Deadline',
            'description' => 'Critical project submission for the semester evaluation.',
            'remaining' => '12 Days, 4h',
        ];

        $team = [
            ['name' => 'Dr. Elena Rodriguez', 'role' => 'Module Lead - Computational Logic', 'initials' => 'ER'],
            ['name' => 'Marcus Chen', 'role' => 'Senior Developer - Algorithms', 'initials' => 'MC'],
            ['name' => 'Sophia Müller', 'role' => 'UI/UX Researcher + CT Visuals', 'initials' => 'SM'],
            ['name' => 'Prof. James Vance', 'role' => 'Subject Advisor - Pedagogy', 'initials' => 'JV'],
        ];

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('WaktuProgres', compact(
            'user', 'namaProjek', 'id', 'daysInMonth', 'monthName', 'tasks', 'months', 'selectedMonth', 'selectedYear',
            'progressMilestone', 'completedTasks', 'totalTasks', 'milestones', 'deadlineCard', 'team'
        ));
    }
}
