<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTaskService;
use App\Services\ProjectWorkspaceService;
use App\Support\ProjectAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo kelas: beberapa dosen, mahasiswa, kelas, dan proyek per kelas.
 * Aman dijalankan ulang: user/kelas/proyek dicari dulu berdasarkan email/judul.
 */
class ClassroomDemoSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    /** Dosen: [nama, email, nidn] */
    private const LECTURERS = [
        'rina' => ['Rina Sihombing, M.Kom.', 'rina@gmail.com', '0101018901'],
        'hendra' => ['Hendra Nainggolan, M.T.', 'hendra@gmail.com', '0102028902'],
        'tigor' => ['Tigor Panjaitan, M.Sc.', 'tigor@gmail.com', '0103038903'],
    ];

    /** Mahasiswa baru: [nama, email, nim] */
    private const STUDENTS = [
        'daniel' => ['Daniel Hutabarat', 'daniel@gmail.com', '11S22004'],
        'grace' => ['Grace Nadeak', 'grace@gmail.com', '11S22005'],
        'ruth' => ['Ruth Sianturi', 'ruth@gmail.com', '11S22006'],
        'kevin' => ['Kevin Situmorang', 'kevin@gmail.com', '11S22007'],
        'tiara' => ['Tiara Br Ginting', 'tiara@gmail.com', '11S22008'],
        'abraham' => ['Abraham Lumbantoruan', 'abraham@gmail.com', '11S22009'],
    ];

    public function __construct(
        private readonly ProjectWorkspaceService $workspace,
        private readonly ProjectTaskService $tasks
    ) {}

    public function run(): void
    {
        $now = Carbon::parse('2026-07-11 10:00:00');

        $users = $this->seedUsers($now);
        $classes = $this->seedClasses($users, $now);
        $this->seedClassMembers($classes, $users, $now);
        $this->seedProjects($classes, $users, $now);

        $this->command->info('Seed kelas & proyek selesai.');
    }

    /** @return array<string, User> */
    private function seedUsers(Carbon $now): array
    {
        $users = [];

        foreach (self::LECTURERS as $key => [$name, $email, $nidn]) {
            $users[$key] = $this->upsertUser($email, [
                'name' => $name,
                'full_name' => $name,
                'username' => explode('@', $email)[0],
                'password' => Hash::make(self::PASSWORD),
                'role' => 'lecturer',
                'nidn' => $nidn,
                'jurusan' => 'S1 Teknik Informatika',
                'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                'email_verified_at' => $now,
            ], $now);
        }

        foreach (self::STUDENTS as $key => [$name, $email, $nim]) {
            $users[$key] = $this->upsertUser($email, [
                'name' => $name,
                'full_name' => $name,
                'username' => explode('@', $email)[0],
                'password' => Hash::make(self::PASSWORD),
                'role' => 'student',
                'nim' => $nim,
                'jurusan' => 'S1 Teknik Informatika',
                'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                'email_verified_at' => $now,
            ], $now);
        }

        // User yang sudah ada dari seeder sebelumnya.
        foreach (['angel', 'mesya', 'budi', 'sari', 'josua'] as $key) {
            $user = User::query()->where('email', $key.'@gmail.com')->first();

            if ($user) {
                $users[$key] = $user;
            }
        }

        return $users;
    }

    private function upsertUser(string $email, array $attributes, Carbon $now): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            return $user;
        }

        return User::query()->create($attributes + [
            'email' => $email,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array<string, int> */
    private function seedClasses(array $users, Carbon $now): array
    {
        $rows = [
            'rpl_a' => [
                'lecturer' => 'angel',
                'name' => 'Rekayasa Perangkat Lunak - A',
                'course_name' => 'Rekayasa Perangkat Lunak',
                'join_code' => 'RPLA26',
            ],
            'basdat_b' => [
                'lecturer' => 'rina',
                'name' => 'Basis Data Lanjut - B',
                'course_name' => 'Basis Data Lanjut',
                'join_code' => 'BDLB26',
            ],
            'web_c' => [
                'lecturer' => 'hendra',
                'name' => 'Pemrograman Web - C',
                'course_name' => 'Pemrograman Web',
                'join_code' => 'PWEBC26',
            ],
            'ai_d' => [
                'lecturer' => 'tigor',
                'name' => 'Kecerdasan Buatan - D',
                'course_name' => 'Kecerdasan Buatan',
                'join_code' => 'AID26',
            ],
        ];

        $ids = [];

        foreach ($rows as $key => $row) {
            $existing = DB::table('academic_classes')->where('join_code', $row['join_code'])->value('id');

            $ids[$key] = (int) ($existing ?: DB::table('academic_classes')->insertGetId([
                'lecturer_id' => $users[$row['lecturer']]->id,
                'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                'jurusan' => 'S1 Teknik Informatika',
                'name' => $row['name'],
                'course_name' => $row['course_name'],
                'academic_year' => '2026/2027',
                'semester' => 'Ganjil',
                'description' => 'Kelas '.$row['course_name'].' semester ganjil 2026/2027.',
                'max_members' => 40,
                'join_code' => $row['join_code'],
                'visibility' => 'public',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        return $ids;
    }

    private function seedClassMembers(array $classes, array $users, Carbon $now): void
    {
        $membership = [
            'rpl_a' => ['mesya', 'budi', 'sari', 'josua', 'daniel', 'grace'],
            'basdat_b' => ['daniel', 'grace', 'ruth', 'kevin'],
            'web_c' => ['ruth', 'kevin', 'tiara', 'abraham', 'mesya'],
            'ai_d' => ['tiara', 'abraham', 'josua'],
        ];

        foreach ($membership as $classKey => $studentKeys) {
            foreach ($studentKeys as $studentKey) {
                if (! isset($users[$studentKey])) {
                    continue;
                }

                DB::table('class_members')->updateOrInsert(
                    ['academic_class_id' => $classes[$classKey], 'user_id' => $users[$studentKey]->id],
                    ['joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    private function seedProjects(array $classes, array $users, Carbon $now): void
    {
        $projects = [
            [
                'class' => 'rpl_a',
                'lecturer' => 'angel',
                'pm' => 'budi',
                'members' => ['sari', 'josua'],
                'name' => 'Sistem Informasi Perpustakaan Kampus',
                'group_name' => 'Kelompok 1',
                'problem' => 'Peminjaman buku masih dicatat manual sehingga sulit melacak buku yang telat dikembalikan.',
                'status' => 'active',
            ],
            [
                'class' => 'rpl_a',
                'lecturer' => 'angel',
                'pm' => 'daniel',
                'members' => ['grace'],
                'name' => 'Aplikasi Presensi QR Mahasiswa',
                'group_name' => 'Kelompok 2',
                'problem' => 'Presensi kuliah dengan tanda tangan kertas rawan titip absen.',
                'status' => 'pending_approval',
            ],
            [
                'class' => 'basdat_b',
                'lecturer' => 'rina',
                'pm' => 'ruth',
                'members' => ['kevin', 'daniel'],
                'name' => 'Data Warehouse Penjualan UMKM',
                'group_name' => 'Kelompok A',
                'problem' => 'Data penjualan UMKM tersebar di banyak spreadsheet sehingga sulit dianalisis.',
                'status' => 'active',
            ],
            [
                'class' => 'basdat_b',
                'lecturer' => 'rina',
                'pm' => 'grace',
                'members' => ['kevin'],
                'name' => 'Optimasi Query Sistem Akademik',
                'group_name' => 'Kelompok B',
                'problem' => 'Query laporan nilai berjalan lambat saat semester berakhir.',
                'status' => 'pending_approval',
            ],
            [
                'class' => 'web_c',
                'lecturer' => 'hendra',
                'pm' => 'tiara',
                'members' => ['abraham', 'kevin'],
                'name' => 'Marketplace Produk Lokal Toba',
                'group_name' => 'Kelompok Toba',
                'problem' => 'Pengrajin lokal Toba belum punya kanal penjualan daring bersama.',
                'status' => 'active',
            ],
            [
                'class' => 'web_c',
                'lecturer' => 'hendra',
                'pm' => 'abraham',
                'members' => ['ruth', 'mesya'],
                'name' => 'Website Profil Desa Wisata',
                'group_name' => 'Kelompok Wisata',
                'problem' => 'Desa wisata belum memiliki media promosi digital yang terkelola.',
                'status' => 'active',
            ],
            [
                'class' => 'ai_d',
                'lecturer' => 'tigor',
                'pm' => 'josua',
                'members' => ['tiara', 'abraham'],
                'name' => 'Klasifikasi Ulasan Wisata dengan NLP',
                'group_name' => 'Kelompok NLP',
                'problem' => 'Ulasan wisatawan sangat banyak dan belum dianalisis untuk perbaikan layanan.',
                'status' => 'draft',
            ],
        ];

        foreach ($projects as $row) {
            if (Project::query()->where('name', $row['name'])->exists()) {
                continue;
            }

            $creator = $users[$row['pm']];
            $lecturer = $users[$row['lecturer']];

            $project = Project::query()->create([
                'name' => $row['name'],
                'academic_class_id' => $classes[$row['class']],
                'group_name' => $row['group_name'],
                'course_name' => DB::table('academic_classes')->where('id', $classes[$row['class']])->value('course_name'),
                'description' => ProjectAccess::formatStoredDescription(
                    'Proyek mata kuliah '.$row['group_name'].' untuk kelas '.$row['class'].'.',
                    $row['problem']
                ),
                'status' => $row['status'],
                'start_date' => $now->copy()->subDays(20)->toDateString(),
                'end_date' => $now->copy()->addMonths(6)->toDateString(),
                'planned_months' => 6,
                'created_by' => $creator->id,
                'lecturer_email' => strtolower($lecturer->email),
                'lecturer_name' => $lecturer->full_name,
                'submitted_at' => $row['status'] === 'draft' ? null : $now->copy()->subDays(18),
            ]);

            $memberEmails = array_map(fn ($key) => $users[$key]->email, $row['members']);

            $this->workspace->initialize($project, $creator, $lecturer->email, $memberEmails);
            $this->tasks->ensureColumns((int) $project->id);

            if ($row['status'] === 'active') {
                $this->seedProjectContent($project, $creator, $users, $row, $now);
            }
        }
    }

    private function seedProjectContent(Project $project, User $creator, array $users, array $row, Carbon $now): void
    {
        $projectId = (int) $project->id;
        $memberIds = array_map(fn ($key) => $users[$key]->id, $row['members']);
        $allIds = array_merge([$creator->id], $memberIds);

        // Masalah utama yang sudah disetujui dosen + satu yang menunggu review.
        DB::table('problem_identifications')->insert([
            [
                'project_id' => $projectId,
                'created_by' => $creator->id,
                'title' => $row['problem'],
                'description' => $row['problem'].' Perlu solusi digital yang terintegrasi.',
                'category' => 'Kebutuhan Proyek',
                'priority' => 'Tinggi',
                'board_status' => 'done',
                'voting_open' => 0,
                'lecturer_feedback' => 'Rumusan masalah sudah jelas, silakan lanjut.',
                'created_at' => $now->copy()->subDays(15),
                'updated_at' => $now,
            ],
            [
                'project_id' => $projectId,
                'created_by' => $memberIds[0] ?? $creator->id,
                'title' => 'Belum ada dokumentasi kebutuhan pengguna',
                'description' => 'Kebutuhan pengguna belum dikumpulkan secara terstruktur.',
                'category' => 'Diskusi',
                'priority' => 'Sedang',
                'board_status' => 'submitted',
                'voting_open' => 0,
                'lecturer_feedback' => null,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now,
            ],
        ]);

        // Beberapa tugas di papan pelaksanaan.
        $milestoneId = (int) DB::table('milestones')->where('project_id', $projectId)->value('id');

        $taskRows = [
            ['Analisis kebutuhan pengguna', 'completed', 'high', 100, -6],
            ['Perancangan basis data', 'completed', 'medium', 100, -2],
            ['Implementasi fitur utama', 'in_progress', 'high', 50, 7],
            ['Pengujian & perbaikan bug', 'pending', 'medium', 0, 14],
            ['Penyusunan laporan akhir', 'pending', 'low', 0, 21],
        ];

        foreach ($taskRows as $i => [$title, $status, $priority, $progress, $dueOffset]) {
            DB::table('tasks')->insert([
                'project_id' => $projectId,
                'board_id' => null,
                'milestone_id' => $milestoneId ?: null,
                'assigned_to' => $allIds[$i % count($allIds)],
                'task_title' => $title,
                'description' => $title.' untuk proyek '.$project->name.'.',
                'priority' => $priority,
                'status' => $status,
                'progress_percent' => $progress,
                'start_date' => $now->copy()->subDays(10)->toDateString(),
                'due_date' => $now->copy()->addDays($dueOffset)->toDateString(),
                'created_at' => $now->copy()->subDays(12),
                'updated_at' => $now,
            ]);
        }

        // Chat proyek singkat.
        DB::table('project_messages')->insert([
            [
                'project_id' => $projectId,
                'user_id' => $creator->id,
                'body' => 'Tim, minggu ini kita fokus ke implementasi fitur utama ya.',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'project_id' => $projectId,
                'user_id' => $memberIds[0] ?? $creator->id,
                'body' => 'Siap, aku lanjutkan bagian basis data yang tersisa.',
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ],
        ]);
    }
}
