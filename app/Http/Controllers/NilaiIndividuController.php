<?php

namespace App\Http\Controllers;

class NilaiIndividuController extends Controller
{
    public function index($id)
    {
        $user = ['name' => 'Daniati Simatupang', 'role' => 'Mahasiswa', 'initials' => 'DS', 'notif_count' => 1];
        
        $studentData = [
            'name' => 'Daniati Simatupang',
            'status' => 'ACTIVE',
            'student_id' => '12124504',
            'department' => 'Computer Science Department',
            'project' => 'Autonomous Greenhouse',
            'role' => 'Project Manager',
            'last_evaluation' => 'Oct 24, 2023',
        ];

        $assessmentMetrics = [
            ['criterion' => 'Project Understanding', 'score' => 82, 'grade' => 'A', 'performance' => 82],
            ['criterion' => 'Activity', 'score' => 89, 'grade' => 'A', 'performance' => 89],
            ['criterion' => 'Presentation', 'score' => 90, 'grade' => 'A', 'performance' => 90],
            ['criterion' => 'Implementation', 'score' => 85, 'grade' => 'B+', 'performance' => 85],
            ['criterion' => 'Documentation', 'score' => 90, 'grade' => 'A', 'performance' => 90],
        ];

        $cumulativeAverage = 90.0;
        $cumulativeGrade = 'A';
        $performanceStatus = 'EXCELLENT';

        $skillsMastery = [
            ['skill' => 'Problem Decomposition', 'percentage' => 98],
            ['skill' => 'Pattern Recognition', 'percentage' => 82],
            ['skill' => 'Algorithm Design', 'percentage' => 95],
        ];

        $systemInteractions = [
            ['label' => 'Comments', 'value' => 142, 'icon' => 'fa-comments'],
            ['label' => 'Tasks Resolved', 'value' => 45, 'icon' => 'fa-check-circle'],
            ['label' => 'Discussions', 'value' => 28, 'icon' => 'fa-comments-dollar'],
        ];

        $lecturerFeedback = '"Daniati has demonstrated exceptional leadership skills throughout the Project development phase. The autonomous greenhouse system shows great technical depth with proper environmental variables and computational thinking principles clearly applied. Documentation shows organized and methodical approach. Great work!"';

        $submittedDate = '01 Oct 2024';

        return view('NilaiIndividu', compact('user', 'studentData', 'assessmentMetrics', 'cumulativeAverage', 'cumulativeGrade', 'performanceStatus', 'skillsMastery', 'systemInteractions', 'lecturerFeedback', 'submittedDate', 'id'));
    }
}

