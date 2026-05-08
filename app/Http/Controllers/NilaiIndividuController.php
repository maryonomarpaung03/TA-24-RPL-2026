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

        $groupEvaluationSummary = [
            'overall_score' => 88,
            'grade' => 'A-',
            'status' => 'Lulus',
            'evaluated_at' => '08 May 2026',
            'evaluator' => 'Dr. Ahmad Faisal',
        ];

        $groupEvaluationComponents = [
            ['component' => 'Kolaborasi Tim', 'score' => 90, 'weight' => '30%'],
            ['component' => 'Kualitas Solusi', 'score' => 87, 'weight' => '35%'],
            ['component' => 'Dokumentasi', 'score' => 85, 'weight' => '20%'],
            ['component' => 'Presentasi', 'score' => 89, 'weight' => '15%'],
        ];

        $groupMemberScores = [
            ['name' => 'Daniati Simatupang', 'score' => 90],
            ['name' => 'Niko Tarigan', 'score' => 86],
            ['name' => 'Rehan Hutabarat', 'score' => 88],
        ];

        $groupLecturerNote = 'Tim menunjukkan kerja sama yang baik dan output proyek stabil. Pertahankan konsistensi dokumentasi dan pengujian untuk fase akhir.';

        return view('NilaiIndividu', compact(
            'user',
            'studentData',
            'assessmentMetrics',
            'cumulativeAverage',
            'cumulativeGrade',
            'performanceStatus',
            'skillsMastery',
            'systemInteractions',
            'lecturerFeedback',
            'submittedDate',
            'groupEvaluationSummary',
            'groupEvaluationComponents',
            'groupMemberScores',
            'groupLecturerNote',
            'id'
        ));
    }
}

