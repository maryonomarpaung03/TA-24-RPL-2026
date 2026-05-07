<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User (for created_by)
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin DELPRO',
            'email' => 'admin@delpro.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Projects
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Aplikasi Absensi Online Berbasis QR Code',
            'description' => 'Sistem manajemen absensi digital menggunakan QR code untuk meningkatkan efisiensi dan akurasi pencatatan kehadiran',
            'status' => 'active',
            'start_date' => '2026-02-01',
            'end_date' => '2026-05-31',
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Group
        $groupId = DB::table('project_groups')->insertGetId([
            'project_id' => $projectId,
            'group_name' => 'Group 4 - Smart Irrigation System',
            'description' => 'Semester 5 • Advanced PSL Workshop',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add Group Members
        $members = [
            ['name' => 'Anggota Atas', 'email' => 'anggota1@example.com'],
            ['name' => 'Daniati Simatupang', 'email' => 'daniati@example.com'],
            ['name' => 'Rehan', 'email' => 'rehan@example.com'],
            ['name' => 'Niko', 'email' => 'niko@example.com'],
        ];

        foreach ($members as $member) {
            $userId = DB::table('users')->insertGetId([
                'name' => $member['name'],
                'email' => $member['email'],
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('group_members')->insert([
                'group_id' => $groupId,
                'user_id' => $userId,
                'role' => $member['email'] === 'anggota1@example.com' ? 'lead' : 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add Milestones
        $milestones = [
            ['phase' => 'Phase 1 Planning', 'status' => 'done', 'deadline' => '2026-02-28'],
            ['phase' => 'Phase 2 Execution', 'status' => 'done', 'deadline' => '2026-04-15'],
            ['phase' => 'Phase 3 Final Report', 'status' => 'in-review', 'deadline' => '2026-05-31'],
        ];

        foreach ($milestones as $milestone) {
            DB::table('group_milestones')->insert([
                'group_id' => $groupId,
                'phase' => $milestone['phase'],
                'status' => $milestone['status'],
                'deadline' => $milestone['deadline'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add CT Metrics
        $ctMetrics = [
            ['metric_name' => 'Abstraction', 'score' => 8.5],
            ['metric_name' => 'Computational Logic', 'score' => 9.2],
            ['metric_name' => 'Problem Decomposition', 'score' => 7.8],
        ];

        foreach ($ctMetrics as $metric) {
            DB::table('ct_metrics')->insert([
                'group_id' => $groupId,
                'metric_name' => $metric['metric_name'],
                'score' => $metric['score'],
                'max_score' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add Peer Reviews
        $peerReviews = [
            ['category' => 'Technical', 'score' => 4.2],
            ['category' => 'Reliability', 'score' => 4.8],
            ['category' => 'Testing', 'score' => 4.5],
            ['category' => 'Conflict Res', 'score' => 3.9],
        ];

        foreach ($peerReviews as $review) {
            DB::table('peer_reviews')->insert([
                'group_id' => $groupId,
                'category' => $review['category'],
                'score' => $review['score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add Lecturer Evaluation
        $lecturerId = DB::table('users')->insertGetId([
            'name' => 'Dr. Ahmad Faisal',
            'email' => 'ahmad.faisal@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('group_evaluations')->insert([
            'group_id' => $groupId,
            'lecturer_id' => $lecturerId,
            'feedback' => 'Kelompok 4 menunjukkan sinergi yang sangat baik dalam fase desain dan implementasi algoritma. Apresiasi kami kepada persiapan yang matang sebelum submission fase akhir dilaksanakan. Saya sangat terkesan dengan dokumentasi integrasi API di fase berkunjung.',
            'overall_score' => 8.8,
            'status' => 'finalized',
            'evaluated_at' => now()->subDays(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
