<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\ProjectTaskService;
use App\Services\ProjectWorkspaceService;
use App\Support\ProjectAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo dalam jumlah besar: banyak dosen, mahasiswa, kelas, dan proyek
 * beserta isinya (masalah, dekomposisi, tugas, approval, chat, penilaian).
 *
 * Deterministik dan idempotent: user dicari dari email, kelas dari join_code,
 * proyek dari judul. Menjalankan ulang tidak menggandakan data.
 */
class BulkDemoSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    /** Dosen tambahan: [nama, email, nidn] */
    private const LECTURERS = [
        ['Dr. Marta Sinaga, M.Kom.', 'marta@gmail.com', '0104048904'],
        ['Fernando Tampubolon, M.T.', 'fernando@gmail.com', '0105058905'],
        ['Yohana Manullang, M.Kom.', 'yohana@gmail.com', '0106068906'],
        ['Parlin Situmeang, M.Sc.', 'parlin@gmail.com', '0107078907'],
        ['Rosa Sagala, M.Kom.', 'rosa@gmail.com', '0108088908'],
    ];

    /** Mahasiswa tambahan (nama depan dipakai untuk email). */
    private const STUDENT_NAMES = [
        'Agnes Simarmata', 'Bintang Purba', 'Cindy Sitompul', 'Dedi Marpaung',
        'Elisa Tobing', 'Fajar Napitupulu', 'Gita Silalahi', 'Hotma Siregar',
        'Indra Pardede', 'Juwita Samosir', 'Kristian Sihotang', 'Lidya Butarbutar',
        'Marto Aritonang', 'Nadia Hutapea', 'Oloan Sibarani', 'Putri Simatupang',
        'Renaldi Manik', 'Sonia Gultom', 'Tommy Sitorus', 'Ulina Damanik',
        'Victor Sipahutar', 'Winda Tarigan', 'Yosua Situngkir', 'Zaskia Munthe',
        'Andreas Simbolon', 'Bella Hasibuan', 'Chandra Nababan', 'Dewi Sianipar',
        'Erik Hutasoit', 'Feby Pasaribu',
    ];

    /** Kelas: [nama, mata kuliah, kode, index dosen] */
    private const CLASSES = [
        ['Algoritma & Struktur Data - A', 'Algoritma & Struktur Data', 'ASDA26', 0],
        ['Algoritma & Struktur Data - B', 'Algoritma & Struktur Data', 'ASDB26', 0],
        ['Jaringan Komputer - A', 'Jaringan Komputer', 'JARKOMA26', 1],
        ['Sistem Operasi - A', 'Sistem Operasi', 'SISOPA26', 2],
        ['Interaksi Manusia & Komputer - A', 'Interaksi Manusia & Komputer', 'IMKA26', 3],
        ['Manajemen Proyek TI - A', 'Manajemen Proyek TI', 'MPTIA26', 4],
        ['Keamanan Siber - A', 'Keamanan Siber', 'CYBERA26', 1],
        ['Analisis Data - A', 'Analisis Data', 'DATAA26', 4],
    ];

    /** Judul proyek + masalah utamanya. */
    private const PROJECT_IDEAS = [
        ['Sistem Antrian Klinik Digital', 'Pasien menunggu lama karena antrian klinik masih memakai nomor kertas.'],
        ['Aplikasi Bank Sampah Desa', 'Warga kesulitan mencatat setoran sampah dan konversi poinnya.'],
        ['Portal Lowongan Magang Kampus', 'Informasi lowongan magang tersebar di banyak grup dan mudah terlewat.'],
        ['Sistem Monitoring Kualitas Air Danau', 'Data kualitas air Danau Toba dicatat manual dan tidak real-time.'],
        ['Aplikasi Kas Organisasi Mahasiswa', 'Kas organisasi dicatat di buku sehingga sulit diaudit.'],
        ['Sistem Peminjaman Ruang Kelas', 'Peminjaman ruang sering bentrok karena dicatat di papan tulis.'],
        ['Aplikasi Pengingat Jadwal Kuliah', 'Mahasiswa sering lupa perubahan jadwal kuliah mendadak.'],
        ['Platform Donasi Bencana Daerah', 'Penyaluran donasi tidak transparan dan sulit dilacak.'],
        ['Sistem Inventaris Laboratorium', 'Alat laboratorium hilang tanpa catatan peminjaman yang jelas.'],
        ['Aplikasi Kesehatan Mental Mahasiswa', 'Mahasiswa enggan konsultasi karena tidak ada kanal yang aman.'],
        ['Sistem Pemesanan Kantin Kampus', 'Antrian kantin panjang saat jam istirahat.'],
        ['Dashboard Statistik UMKM Daerah', 'Data UMKM daerah tidak terkumpul dalam satu dasbor.'],
        ['Aplikasi Absensi Berbasis Lokasi', 'Absensi kerja praktik sulit diverifikasi lokasinya.'],
        ['Sistem Rekomendasi Buku Perpustakaan', 'Mahasiswa kesulitan menemukan referensi yang relevan.'],
        ['Platform Belajar Bahasa Batak', 'Generasi muda kehilangan akses belajar bahasa daerah.'],
        ['Sistem Pelaporan Kerusakan Fasilitas', 'Laporan kerusakan fasilitas kampus lambat ditindaklanjuti.'],
    ];

    private const TASK_TEMPLATES = [
        ['Wawancara & analisis kebutuhan', 'completed', 'high'],
        ['Menyusun dokumen SRS', 'completed', 'high'],
        ['Perancangan basis data', 'completed', 'medium'],
        ['Perancangan antarmuka (mockup)', 'in_progress', 'medium'],
        ['Implementasi modul utama', 'in_progress', 'high'],
        ['Implementasi modul pendukung', 'in_progress', 'medium'],
        ['Integrasi & pengujian modul', 'pending', 'high'],
        ['Pengujian black box', 'pending', 'medium'],
        ['Penyusunan laporan akhir', 'pending', 'low'],
        ['Persiapan presentasi', 'pending', 'low'],
    ];

    public function __construct(
        private readonly ProjectWorkspaceService $workspace,
        private readonly ProjectTaskService $tasks,
        private readonly EvaluationService $evaluations
    ) {}

    public function run(): void
    {
        $now = Carbon::parse('2026-07-11 10:00:00');

        $lecturers = $this->seedLecturers($now);
        $students = $this->seedStudents($now);
        $classes = $this->seedClasses($lecturers, $now);
        $this->seedClassMembers($classes, $students, $now);
        $this->seedProjects($classes, $lecturers, $students, $now);

        $this->command->info(sprintf(
            'Selesai. Total: %d dosen, %d mahasiswa, %d kelas, %d proyek.',
            User::query()->where('role', 'lecturer')->count(),
            User::query()->where('role', 'student')->count(),
            DB::table('academic_classes')->count(),
            Project::query()->count()
        ));
    }

    /** @return list<User> */
    private function seedLecturers(Carbon $now): array
    {
        $lecturers = [];

        foreach (self::LECTURERS as [$name, $email, $nidn]) {
            $lecturers[] = $this->upsertUser($email, [
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

        return $lecturers;
    }

    /** @return list<User> */
    private function seedStudents(Carbon $now): array
    {
        $students = [];

        foreach (self::STUDENT_NAMES as $i => $name) {
            $email = strtolower(explode(' ', $name)[0]).'@gmail.com';

            $students[] = $this->upsertUser($email, [
                'name' => $name,
                'full_name' => $name,
                'username' => explode('@', $email)[0],
                'password' => Hash::make(self::PASSWORD),
                'role' => 'student',
                'nim' => sprintf('11S23%03d', $i + 1),
                'jurusan' => 'S1 Teknik Informatika',
                'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                'email_verified_at' => $now,
            ], $now);
        }

        return $students;
    }

    private function upsertUser(string $email, array $attributes, Carbon $now): User
    {
        return User::query()->where('email', $email)->first()
            ?? User::query()->create($attributes + [
                'email' => $email,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }

    /** @return list<int> */
    private function seedClasses(array $lecturers, Carbon $now): array
    {
        $ids = [];

        foreach (self::CLASSES as [$name, $course, $code, $lecturerIndex]) {
            $existing = DB::table('academic_classes')->where('join_code', $code)->value('id');

            $ids[] = (int) ($existing ?: DB::table('academic_classes')->insertGetId([
                'lecturer_id' => $lecturers[$lecturerIndex]->id,
                'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                'jurusan' => 'S1 Teknik Informatika',
                'name' => $name,
                'course_name' => $course,
                'academic_year' => '2026/2027',
                'semester' => 'Ganjil',
                'description' => 'Kelas '.$course.' semester ganjil 2026/2027.',
                'max_members' => 40,
                'join_code' => $code,
                'visibility' => 'public',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        return $ids;
    }

    /** Tiap kelas diisi 10 mahasiswa, digeser 5 orang agar ada yang lintas kelas. */
    private function seedClassMembers(array $classes, array $students, Carbon $now): void
    {
        $total = count($students);

        foreach ($classes as $i => $classId) {
            for ($n = 0; $n < 10; $n++) {
                $student = $students[($i * 5 + $n) % $total];

                DB::table('class_members')->updateOrInsert(
                    ['academic_class_id' => $classId, 'user_id' => $student->id],
                    ['joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    /** Dua proyek per kelas: satu aktif berisi lengkap, satu variasi status. */
    private function seedProjects(array $classes, array $lecturers, array $students, Carbon $now): void
    {
        $ideaIndex = 0;
        $statusCycle = ['active', 'active', 'pending_approval', 'active', 'draft', 'active', 'pending_approval', 'completed'];

        foreach ($classes as $i => $classId) {
            $class = DB::table('academic_classes')->find($classId);
            $lecturer = User::query()->find($class->lecturer_id);

            $classStudents = DB::table('class_members')
                ->where('academic_class_id', $classId)
                ->pluck('user_id')
                ->all();

            for ($g = 0; $g < 2; $g++) {
                [$title, $problem] = self::PROJECT_IDEAS[$ideaIndex % count(self::PROJECT_IDEAS)];
                $ideaIndex++;

                if (Project::query()->where('name', $title)->exists()) {
                    continue;
                }

                // Tiap kelompok mengambil 4 mahasiswa berbeda dari kelas.
                $memberIds = array_slice($classStudents, $g * 4, 4);

                if (count($memberIds) < 2) {
                    continue;
                }

                $creator = User::query()->find(array_shift($memberIds));
                $members = User::query()->whereIn('id', $memberIds)->get();
                $status = $statusCycle[($i * 2 + $g) % count($statusCycle)];

                $project = Project::query()->create([
                    'name' => $title,
                    'academic_class_id' => $classId,
                    'group_name' => 'Kelompok '.($g + 1),
                    'course_name' => $class->course_name,
                    'description' => ProjectAccess::formatStoredDescription(
                        'Proyek '.$class->course_name.' kelas '.$class->name.'.',
                        $problem
                    ),
                    'status' => $status,
                    'start_date' => $now->copy()->subDays(30)->toDateString(),
                    'end_date' => $now->copy()->addMonths(6)->toDateString(),
                    'planned_months' => 6,
                    'created_by' => $creator->id,
                    'lecturer_email' => strtolower($lecturer->email),
                    'lecturer_name' => $lecturer->full_name,
                    'submitted_at' => $status === 'draft' ? null : $now->copy()->subDays(25),
                ]);

                $this->workspace->initialize(
                    $project,
                    $creator,
                    $lecturer->email,
                    $members->pluck('email')->all()
                );
                $this->tasks->ensureColumns((int) $project->id);

                if (in_array($status, ['active', 'completed'], true)) {
                    $this->fillProject($project, $creator, $members->all(), $lecturer, $problem, $now);
                }
            }
        }
    }

    /** @param list<User> $members */
    private function fillProject(
        Project $project,
        User $creator,
        array $members,
        User $lecturer,
        string $problem,
        Carbon $now
    ): void {
        $projectId = (int) $project->id;
        $team = array_merge([$creator], $members);
        $teamIds = array_map(fn (User $u) => $u->id, $team);

        $this->fillProblems($projectId, $teamIds, $problem, $now);
        $this->fillDecomposition($projectId, $creator, $now);
        $taskIds = $this->fillTasks($projectId, $teamIds, $project->name, $now);
        $this->fillDiscussions($projectId, $taskIds, $teamIds, $lecturer->id, $now);
        $this->fillApprovals($projectId, $taskIds, $teamIds, $lecturer->id, $now);
        $this->fillChat($projectId, $teamIds, $now);

        // Proyek yang sudah selesai sekalian diberi nilai oleh dosen.
        if ($project->status === 'completed') {
            $this->fillEvaluation($projectId, $lecturer->id, $team, $now);
        }
    }

    private function fillProblems(int $projectId, array $teamIds, string $problem, Carbon $now): void
    {
        if (DB::table('problem_identifications')->where('project_id', $projectId)->exists()) {
            return;
        }

        $rows = [
            [$teamIds[0], $problem, 'Kebutuhan Proyek', 'Tinggi', 'done', 0, 'Rumusan masalah sudah tajam. Lanjutkan.'],
            [$teamIds[1] ?? $teamIds[0], 'Proses pencatatan masih tersebar di banyak tempat', 'Diskusi', 'Sedang', 'submitted', 0, null],
            [$teamIds[2] ?? $teamIds[0], 'Belum ada standar pelaporan yang seragam', 'Teknik', 'Sedang', 'voting', 1, null],
            [$teamIds[0], 'Pengguna belum terbiasa dengan sistem digital', 'Etika', 'Rendah', 'idea', 0, null],
        ];

        foreach ($rows as $i => [$by, $title, $category, $priority, $status, $votingOpen, $feedback]) {
            $problemId = DB::table('problem_identifications')->insertGetId([
                'project_id' => $projectId,
                'created_by' => $by,
                'title' => $title,
                'description' => $title.'. Perlu dianalisis lebih lanjut bersama tim.',
                'category' => $category,
                'priority' => $priority,
                'board_status' => $status,
                'voting_open' => $votingOpen,
                'voting_deadline' => $votingOpen ? $now->copy()->addDays(3) : null,
                'lecturer_feedback' => $feedback,
                'created_at' => $now->copy()->subDays(20 - $i * 3),
                'updated_at' => $now,
            ]);

            if ($votingOpen) {
                foreach (array_slice($teamIds, 0, 2) as $voter) {
                    DB::table('problem_votes')->insert([
                        'problem_id' => $problemId,
                        'project_id' => $projectId,
                        'user_id' => $voter,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function fillDecomposition(int $projectId, User $creator, Carbon $now): void
    {
        if (DB::table('decomposition_nodes')->where('project_id', $projectId)->exists()) {
            return;
        }

        $nodes = [
            ['root', 'Solusi Sistem', 'circle', '#dbeafe', 460, 80],
            ['n1', 'Manajemen Data', 'rect', '#dcfce7', 220, 240],
            ['n2', 'Proses Utama', 'rect', '#fef9c3', 460, 240],
            ['n3', 'Pelaporan', 'rect', '#fee2e2', 700, 240],
            ['n1a', 'Input & validasi data', 'rect', '#f1f5f9', 220, 400],
            ['n2a', 'Alur transaksi', 'rect', '#f1f5f9', 460, 400],
            ['n3a', 'Dasbor & ekspor', 'rect', '#f1f5f9', 700, 400],
        ];

        foreach ($nodes as $i => [$key, $title, $shape, $color, $x, $y]) {
            DB::table('decomposition_nodes')->insert([
                'project_id' => $projectId,
                'node_key' => $key,
                'title' => $title,
                'shape' => $shape,
                'color' => $color,
                'pos_x' => $x,
                'pos_y' => $y,
                'created_by' => $creator->full_name,
                'created_at_label' => $now->copy()->subDays(18)->format('Y-m-d'),
                'created_at' => $now->copy()->subDays(18)->addMinutes($i * 3),
                'updated_at' => $now,
            ]);
        }

        foreach ([['root', 'n1'], ['root', 'n2'], ['root', 'n3'], ['n1', 'n1a'], ['n2', 'n2a'], ['n3', 'n3a']] as [$from, $to]) {
            DB::table('decomposition_connections')->insert([
                'project_id' => $projectId,
                'from_node' => $from,
                'to_node' => $to,
                'created_at' => $now->copy()->subDays(18),
                'updated_at' => $now,
            ]);
        }

        DB::table('decomposition_comments')->insert([
            'project_id' => $projectId,
            'author_name' => $creator->full_name,
            'author_initials' => ProjectAccess::initialsFromName($creator->full_name),
            'comment_text' => 'Cabang pelaporan perlu ditambah sub-node ekspor PDF.',
            'created_at' => $now->copy()->subDays(16),
            'updated_at' => $now,
        ]);

        DB::table('decomposition_submissions')->insert([
            'project_id' => $projectId,
            'submitted_by' => $creator->id,
            'nodes_snapshot' => json_encode(DB::table('decomposition_nodes')->where('project_id', $projectId)->get()),
            'connections_snapshot' => json_encode(DB::table('decomposition_connections')->where('project_id', $projectId)->get()),
            'comments_snapshot' => json_encode(DB::table('decomposition_comments')->where('project_id', $projectId)->get()),
            'status' => 'submitted',
            'created_at' => $now->copy()->subDays(15),
            'updated_at' => $now->copy()->subDays(15),
        ]);
    }

    /** @return list<int> */
    private function fillTasks(int $projectId, array $teamIds, string $projectName, Carbon $now): array
    {
        if (DB::table('tasks')->where('project_id', $projectId)->exists()) {
            return DB::table('tasks')->where('project_id', $projectId)->pluck('id')->all();
        }

        // Kolom papan sekaligus status tugas: pending / in_progress / completed.
        app(ProjectTaskService::class)->ensureColumns($projectId);

        $milestoneId = (int) DB::table('milestones')->where('project_id', $projectId)->value('id');
        $ids = [];

        foreach (self::TASK_TEMPLATES as $i => [$title, $status, $priority]) {
            $progress = match ($status) {
                'completed' => 100,
                'in_progress' => 40 + ($i * 7) % 50,
                default => 0,
            };

            $ids[] = (int) DB::table('tasks')->insertGetId([
                'project_id' => $projectId,
                'milestone_id' => $milestoneId ?: null,
                'assigned_to' => $teamIds[$i % count($teamIds)],
                'task_title' => $title,
                'description' => $title.' untuk proyek '.$projectName.'.',
                'priority' => $priority,
                'status' => $status,
                'progress_percent' => $progress,
                'start_date' => $now->copy()->subDays(20 - $i)->toDateString(),
                'due_date' => $now->copy()->addDays($i * 3 - 5)->toDateString(),
                'created_at' => $now->copy()->subDays(22),
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    private function fillDiscussions(int $projectId, array $taskIds, array $teamIds, int $lecturerId, Carbon $now): void
    {
        $messages = [
            'Bagian ini sudah kukerjakan, tolong direview ya.',
            'Ada kendala di validasi data, mungkin perlu diskusi.',
            'Sudah kuperbaiki sesuai masukan kemarin.',
            'Pastikan penamaan variabel konsisten dengan dokumen SRS.',
        ];

        foreach (array_slice($taskIds, 0, 4) as $i => $taskId) {
            $userId = $i === 3 ? $lecturerId : $teamIds[$i % count($teamIds)];

            DB::table('discussions')->insert([
                'project_id' => $projectId,
                'user_id' => $userId,
                'task_id' => $taskId,
                'problem_id' => null,
                'parent_id' => null,
                'message' => $messages[$i],
                'created_at' => $now->copy()->subDays(5)->addHours($i),
            ]);

            DB::table('task_comments')->insert([
                'task_id' => $taskId,
                'comment' => $messages[$i],
                'created_at' => $now->copy()->subDays(5)->addHours($i),
                'updated_at' => $now,
            ]);
        }
    }

    private function fillApprovals(int $projectId, array $taskIds, array $teamIds, int $lecturerId, Carbon $now): void
    {
        DB::table('project_task_columns')
            ->where('project_id', $projectId)
            ->where('key', 'completed')
            ->update(['requires_approval' => 1, 'updated_at' => $now]);

        if (count($taskIds) < 5 || DB::table('task_approvals')->where('project_id', $projectId)->exists()) {
            return;
        }

        DB::table('task_approvals')->insert([
            [
                'project_id' => $projectId,
                'task_id' => $taskIds[4],
                'from_column_key' => 'in_progress',
                'to_column_key' => 'completed',
                'status' => 'pending',
                'requested_by' => $teamIds[0],
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(8),
            ],
            [
                'project_id' => $projectId,
                'task_id' => $taskIds[1],
                'from_column_key' => 'in_progress',
                'to_column_key' => 'completed',
                'status' => 'approved',
                'requested_by' => $teamIds[1 % count($teamIds)],
                'reviewed_by' => $lecturerId,
                'reviewed_at' => $now->copy()->subDays(3),
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(3),
            ],
        ]);
    }

    private function fillChat(int $projectId, array $teamIds, Carbon $now): void
    {
        if (DB::table('project_messages')->where('project_id', $projectId)->exists()) {
            return;
        }

        $messages = [
            'Halo tim, ini grup diskusi proyek kita ya.',
            'Aku sudah push progres modul utama ke repositori.',
            'Jangan lupa besok kita demo ke dosen pembimbing.',
            'Siap, aku siapkan slide presentasinya.',
        ];

        foreach ($messages as $i => $body) {
            DB::table('project_messages')->insert([
                'project_id' => $projectId,
                'user_id' => $teamIds[$i % count($teamIds)],
                'body' => $body,
                'created_at' => $now->copy()->subDays(3)->addHours($i * 2),
                'updated_at' => $now->copy()->subDays(3)->addHours($i * 2),
            ]);
        }
    }

    /** @param list<User> $team */
    private function fillEvaluation(int $projectId, int $lecturerId, array $team, Carbon $now): void
    {
        if (DB::table('project_evaluations')->where('project_id', $projectId)->exists()) {
            return;
        }

        $components = [];
        foreach (array_keys(EvaluationService::COMPONENTS) as $i => $key) {
            $components[$key] = 82 + ($i * 3) % 12;
        }

        $students = [];
        foreach ($team as $i => $member) {
            $base = 80 + ($i * 4) % 15;

            $criteria = [];
            foreach (array_keys(EvaluationService::CRITERIA) as $j => $key) {
                $criteria[$key] = min(100, $base + ($j * 2) % 8);
            }

            $students[$member->id] = [
                'score' => $base,
                'feedback' => 'Kontribusi '.$member->full_name.' pada proyek ini konsisten. Pertahankan.',
                'criteria' => $criteria,
            ];
        }

        $this->evaluations->saveLecturerEvaluation(
            $projectId,
            $lecturerId,
            87,
            $components,
            'Proyek selesai tepat waktu dengan dokumentasi yang rapi.',
            $students
        );

        // Penilaian antar anggota dari tiap mahasiswa.
        foreach ($team as $i => $evaluator) {
            $scores = [];
            foreach ($team as $j => $member) {
                $scores[$member->id] = 85 + (($i + $j) * 3) % 12;
            }

            $this->evaluations->savePeerEvaluation(
                $projectId,
                $evaluator->id,
                86 + ($i * 2) % 8,
                $scores,
                'Kerja sama tim berjalan baik selama proyek berlangsung.'
            );
        }
    }
}
