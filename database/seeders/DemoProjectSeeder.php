<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

/**
 * Data demo untuk proyek "Pengembangan Sistem HKBP" (project id 4).
 * Mahasiswa: Mesya (owner) + 3 anggota baru. Dosen: angel@gmail.com.
 * Aman dijalankan ulang (idempotent): data lama milik proyek ini dibersihkan dulu.
 */
class DemoProjectSeeder extends Seeder
{
    private const PROJECT_ID = 4;

    public function run(): void
    {
        $project = DB::table('projects')->find(self::PROJECT_ID);

        if (! $project) {
            $this->command->error('Project id '.self::PROJECT_ID.' tidak ditemukan.');

            return;
        }

        $now = Carbon::parse('2026-07-11 09:00:00');

        $mesya = (int) DB::table('users')->where('email', 'mesya@gmail.com')->value('id');
        $angel = (int) DB::table('users')->where('email', 'angel@gmail.com')->value('id');

        $students = $this->seedStudents($now);
        $students = ['mesya' => $mesya] + $students;

        $this->attachToClassAndGroup($project, $students, $now);
        $this->cleanupProjectData();

        $problems = $this->seedProblems($students, $angel, $now);
        $this->seedDecomposition($students, $now);
        $boards = $this->seedBoards($now);
        $tasks = $this->seedTasks($students, $boards, $now);
        $this->seedTaskDiscussions($tasks, $students, $angel, $now);
        $this->seedApprovals($tasks, $students, $now);
        $this->seedChatAndNotifications($students, $now, $problems);

        $this->command->info('Seed selesai untuk project '.self::PROJECT_ID.'.');
    }

    /** @return array<string, int> */
    private function seedStudents(Carbon $now): array
    {
        $rows = [
            'budi' => ['Budi Simanjuntak', 'budi@gmail.com', '11S22001'],
            'sari' => ['Sari Panggabean', 'sari@gmail.com', '11S22002'],
            'josua' => ['Josua Sitorus', 'josua@gmail.com', '11S22003'],
        ];

        $ids = [];

        foreach ($rows as $key => [$fullName, $email, $nim]) {
            $id = DB::table('users')->where('email', $email)->value('id');

            if (! $id) {
                $id = DB::table('users')->insertGetId([
                    'name' => $fullName,
                    'full_name' => $fullName,
                    'username' => explode('@', $email)[0],
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                    'nim' => $nim,
                    'jurusan' => 'S1 Teknik Informatika',
                    'fakultas' => 'Fakultas Informatika & Teknik Elektro',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $ids[$key] = (int) $id;
        }

        return $ids;
    }

    /** @param array<string, int> $students */
    private function attachToClassAndGroup(object $project, array $students, Carbon $now): void
    {
        // Proyek dikaitkan ke kelas milik dosen angel supaya muncul di menu kelas dosen.
        $classId = (int) DB::table('academic_classes')->where('lecturer_id', DB::table('users')->where('email', 'angel@gmail.com')->value('id'))->value('id');

        if ($classId && ! $project->academic_class_id) {
            DB::table('projects')->where('id', self::PROJECT_ID)->update([
                'academic_class_id' => $classId,
                'updated_at' => $now,
            ]);
        }

        $groupId = (int) DB::table('project_groups')->where('project_id', self::PROJECT_ID)->value('id');

        foreach ($students as $key => $userId) {
            $email = DB::table('users')->where('id', $userId)->value('email');
            $isOwner = $key === 'mesya';

            if ($classId) {
                DB::table('class_members')->updateOrInsert(
                    ['academic_class_id' => $classId, 'user_id' => $userId],
                    ['joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            DB::table('project_members')->updateOrInsert(
                ['project_id' => self::PROJECT_ID, 'user_id' => $userId],
                [
                    'email' => $email,
                    'role' => $isOwner ? 'owner' : 'member',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if ($groupId) {
                DB::table('group_members')->updateOrInsert(
                    ['group_id' => $groupId, 'user_id' => $userId],
                    [
                        'role' => $isOwner ? 'lead' : 'member',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    private function cleanupProjectData(): void
    {
        $taskIds = DB::table('tasks')->where('project_id', self::PROJECT_ID)->pluck('id');

        DB::table('task_comments')->whereIn('task_id', $taskIds)->delete();
        DB::table('task_approvals')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('tasks')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('project_boards')->where('project_id', self::PROJECT_ID)->delete();

        DB::table('discussions')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('problem_votes')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('problem_identifications')->where('project_id', self::PROJECT_ID)->delete();

        DB::table('decomposition_connections')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('decomposition_nodes')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('decomposition_comments')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('decomposition_submissions')->where('project_id', self::PROJECT_ID)->delete();

        DB::table('project_messages')->where('project_id', self::PROJECT_ID)->delete();
        DB::table('project_notifications')->where('project_id', self::PROJECT_ID)->delete();
    }

    /**
     * @param  array<string, int>  $s
     * @return array<string, int>
     */
    private function seedProblems(array $s, int $angel, Carbon $now): array
    {
        $rows = [
            // done  -> jadi pernyataan masalah yang disetujui dosen (dipakai di dekomposisi)
            'utama' => [
                'created_by' => $s['mesya'],
                'title' => 'Pendataan jemaat dan warta gereja masih manual',
                'description' => 'Data jemaat HKBP masih dicatat di buku induk dan warta dicetak tiap minggu, sehingga rekap kehadiran, iuran, dan pelayanan sulit ditelusuri.',
                'category' => 'Kebutuhan Proyek',
                'priority' => 'Tinggi',
                'board_status' => 'done',
                'voting_open' => 0,
                'lecturer_feedback' => 'Rumusan masalah sudah spesifik dan terukur. Lanjutkan ke tahap dekomposisi.',
                'created_at' => $now->copy()->subDays(9),
            ],
            // submitted -> menunggu review dosen (muncul di halaman Review Masalah Utama)
            'review' => [
                'created_by' => $s['sari'],
                'title' => 'Penjadwalan pelayanan dan ibadah sering bentrok',
                'description' => 'Jadwal pelayanan majelis, koster, dan song leader dibuat terpisah di grup WhatsApp sehingga sering terjadi bentrok jadwal.',
                'category' => 'Diskusi',
                'priority' => 'Sedang',
                'board_status' => 'submitted',
                'voting_open' => 0,
                'lecturer_feedback' => null,
                'created_at' => $now->copy()->subDays(4),
            ],
            // revision -> pernah ditolak dosen, perlu revisi mahasiswa
            'revisi' => [
                'created_by' => $s['josua'],
                'title' => 'Website gereja jarang diperbarui',
                'description' => 'Konten website tidak pernah diperbarui sejak 2023.',
                'category' => 'Teknik',
                'priority' => 'Rendah',
                'board_status' => 'revision',
                'voting_open' => 0,
                'lecturer_feedback' => 'Masalah masih terlalu umum. Sertakan data pendukung dan dampaknya bagi jemaat.',
                'created_at' => $now->copy()->subDays(6),
            ],
            // voting -> sedang divoting anggota kelompok
            'voting' => [
                'created_by' => $s['budi'],
                'title' => 'Laporan keuangan persembahan lambat direkap',
                'description' => 'Rekap persembahan mingguan butuh 3-4 hari karena dihitung manual oleh bendahara.',
                'category' => 'Kebutuhan Proyek',
                'priority' => 'Tinggi',
                'board_status' => 'voting',
                'voting_open' => 1,
                'lecturer_feedback' => null,
                'created_at' => $now->copy()->subDays(2),
            ],
            // idea -> masih ide, belum diajukan voting
            'ide1' => [
                'created_by' => $s['budi'],
                'title' => 'Belum ada arsip digital untuk surat baptis dan sidi',
                'description' => 'Surat baptis/sidi hanya tersimpan dalam bentuk fisik di kantor gereja.',
                'category' => 'Teknik',
                'priority' => 'Sedang',
                'board_status' => 'idea',
                'voting_open' => 0,
                'lecturer_feedback' => null,
                'created_at' => $now->copy()->subDay(),
            ],
            'ide2' => [
                'created_by' => $s['mesya'],
                'title' => 'Tidak ada notifikasi kegiatan untuk jemaat muda',
                'description' => 'Informasi kegiatan Naposo hanya lewat pengumuman mimbar sehingga banyak yang terlewat.',
                'category' => 'Diskusi',
                'priority' => 'Rendah',
                'board_status' => 'idea',
                'voting_open' => 0,
                'lecturer_feedback' => null,
                'created_at' => $now->copy()->subHours(20),
            ],
        ];

        $ids = [];

        foreach ($rows as $key => $row) {
            $ids[$key] = (int) DB::table('problem_identifications')->insertGetId([
                'project_id' => self::PROJECT_ID,
                'created_by' => $row['created_by'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category' => $row['category'],
                'priority' => $row['priority'],
                'attachment_link' => null,
                'board_status' => $row['board_status'],
                'voting_open' => $row['voting_open'],
                'voting_deadline' => $row['voting_open'] ? $now->copy()->addDays(2) : null,
                'lecturer_feedback' => $row['lecturer_feedback'],
                'created_at' => $row['created_at'],
                'updated_at' => $now,
            ]);
        }

        // Voting: 3 dari 4 anggota memilih masalah keuangan.
        foreach ([$s['mesya'], $s['budi'], $s['josua']] as $voter) {
            DB::table('problem_votes')->insert([
                'problem_id' => $ids['voting'],
                'project_id' => self::PROJECT_ID,
                'user_id' => $voter,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Diskusi pada masalah (komentar mahasiswa + catatan dosen).
        $discussions = [
            [$s['budi'], $ids['utama'], 'Setuju, data buku induk banyak yang tidak sinkron dengan data amplop persembahan.'],
            [$s['sari'], $ids['utama'], 'Aku sudah wawancara sekretaris gereja, nanti kulampirkan hasilnya.'],
            [$angel, $ids['utama'], 'Bagus. Pastikan ruang lingkupnya dibatasi agar selesai dalam satu semester.'],
            [$s['mesya'], $ids['voting'], 'Kalau ini yang dipilih, kita perlu akses data bendahara dulu.'],
            [$angel, $ids['revisi'], 'Tolong dilengkapi datanya sebelum diajukan lagi.'],
        ];

        foreach ($discussions as $i => [$userId, $problemId, $message]) {
            DB::table('discussions')->insert([
                'project_id' => self::PROJECT_ID,
                'user_id' => $userId,
                'task_id' => null,
                'problem_id' => $problemId,
                'parent_id' => null,
                'message' => $message,
                'created_at' => $now->copy()->subDays(3)->addHours($i),
            ]);
        }

        return $ids;
    }

    /** @param array<string, int> $s */
    private function seedDecomposition(array $s, Carbon $now): void
    {
        $nodes = [
            ['root', 'Sistem Informasi Gereja HKBP', 'circle', '#dbeafe', 460, 80, 'Mesya'],
            ['n1', 'Manajemen Data Jemaat', 'rect', '#dcfce7', 200, 240, 'Mesya'],
            ['n2', 'Keuangan & Persembahan', 'rect', '#fef9c3', 460, 240, 'Budi Simanjuntak'],
            ['n3', 'Penjadwalan Pelayanan', 'rect', '#fee2e2', 720, 240, 'Sari Panggabean'],
            ['n1a', 'Pendaftaran & profil jemaat', 'rect', '#f1f5f9', 120, 400, 'Josua Sitorus'],
            ['n1b', 'Arsip baptis & sidi', 'rect', '#f1f5f9', 320, 400, 'Josua Sitorus'],
            ['n2a', 'Rekap persembahan mingguan', 'rect', '#f1f5f9', 460, 400, 'Budi Simanjuntak'],
            ['n3a', 'Kalender ibadah & petugas', 'rect', '#f1f5f9', 720, 400, 'Sari Panggabean'],
        ];

        foreach ($nodes as $i => [$key, $title, $shape, $color, $x, $y, $author]) {
            DB::table('decomposition_nodes')->insert([
                'project_id' => self::PROJECT_ID,
                'node_key' => $key,
                'title' => $title,
                'shape' => $shape,
                'color' => $color,
                'pos_x' => $x,
                'pos_y' => $y,
                'created_by' => $author,
                'created_at_label' => $now->copy()->subDays(5)->format('Y-m-d'),
                'created_at' => $now->copy()->subDays(5)->addMinutes($i * 5),
                'updated_at' => $now,
            ]);
        }

        $edges = [
            ['root', 'n1'], ['root', 'n2'], ['root', 'n3'],
            ['n1', 'n1a'], ['n1', 'n1b'], ['n2', 'n2a'], ['n3', 'n3a'],
        ];

        foreach ($edges as [$from, $to]) {
            DB::table('decomposition_connections')->insert([
                'project_id' => self::PROJECT_ID,
                'from_node' => $from,
                'to_node' => $to,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now,
            ]);
        }

        $comments = [
            ['Mesya', 'M', 'Cabang keuangan sebaiknya dipisah antara persembahan dan iuran tahunan.'],
            ['Sari Panggabean', 'SP', 'Penjadwalan pelayanan perlu sub-node untuk notifikasi petugas.'],
        ];

        foreach ($comments as $i => [$name, $initials, $text]) {
            DB::table('decomposition_comments')->insert([
                'project_id' => self::PROJECT_ID,
                'author_name' => $name,
                'author_initials' => $initials,
                'comment_text' => $text,
                'created_at' => $now->copy()->subDays(4)->addHours($i),
                'updated_at' => $now,
            ]);
        }

        $nodeSnapshot = DB::table('decomposition_nodes')->where('project_id', self::PROJECT_ID)->get();
        $edgeSnapshot = DB::table('decomposition_connections')->where('project_id', self::PROJECT_ID)->get();
        $commentSnapshot = DB::table('decomposition_comments')->where('project_id', self::PROJECT_ID)->get();

        DB::table('decomposition_submissions')->insert([
            'project_id' => self::PROJECT_ID,
            'submitted_by' => $s['mesya'],
            'nodes_snapshot' => json_encode($nodeSnapshot),
            'connections_snapshot' => json_encode($edgeSnapshot),
            'comments_snapshot' => json_encode($commentSnapshot),
            'status' => 'submitted',
            'created_at' => $now->copy()->subDays(3),
            'updated_at' => $now->copy()->subDays(3),
        ]);
    }

    /** @return array<string, int> nama kolom => board id */
    private function seedBoards(Carbon $now): array
    {
        // Kolom papan mengikuti key di project_task_columns: pending / in_progress / completed
        $boards = [
            'pending' => ['Belum Dikerjakan', 1, false],
            'in_progress' => ['Sedang Dikerjakan', 2, false],
            'completed' => ['Selesai', 3, true],
        ];

        $ids = [];

        foreach ($boards as $key => [$name, $position, $isCompleted]) {
            $ids[$key] = (int) DB::table('project_boards')->insertGetId([
                'project_id' => self::PROJECT_ID,
                'name' => $name,
                'position' => $position,
                'is_completed' => $isCompleted,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Kolom "Selesai" butuh persetujuan dosen -> memunculkan antrean approval.
        DB::table('project_task_columns')
            ->where('project_id', self::PROJECT_ID)
            ->where('key', 'completed')
            ->update(['requires_approval' => 1, 'updated_at' => $now]);

        return $ids;
    }

    /**
     * @param  array<string, int>  $s
     * @param  array<string, int>  $boards
     * @return array<string, int>
     */
    private function seedTasks(array $s, array $boards, Carbon $now): array
    {
        $milestoneId = (int) DB::table('milestones')->where('project_id', self::PROJECT_ID)->value('id');

        $rows = [
            'wawancara' => ['Wawancara sekretaris & bendahara gereja', 'Menggali proses pendataan jemaat dan rekap persembahan saat ini.', $s['sari'], 'completed', 'high', 100, -8, -5],
            'srs' => ['Menyusun dokumen SRS', 'Kebutuhan fungsional & non-fungsional sistem informasi gereja.', $s['mesya'], 'completed', 'high', 100, -7, -3],
            'erd' => ['Perancangan ERD & skema database', 'Entitas jemaat, keluarga, persembahan, jadwal pelayanan.', $s['josua'], 'completed', 'medium', 100, -5, -2],
            'mockup' => ['Membuat mockup UI di Figma', 'Halaman dashboard, data jemaat, dan rekap keuangan.', $s['sari'], 'in_progress', 'medium', 60, -4, 3],
            'modul_jemaat' => ['Implementasi modul data jemaat', 'CRUD jemaat + import data buku induk.', $s['mesya'], 'in_progress', 'high', 45, -3, 6],
            'modul_keuangan' => ['Implementasi modul persembahan', 'Input persembahan mingguan dan rekap otomatis.', $s['budi'], 'in_progress', 'high', 30, -2, 8],
            'modul_jadwal' => ['Implementasi modul penjadwalan pelayanan', 'Kalender petugas ibadah dan pengingat.', $s['josua'], 'pending', 'medium', 0, 1, 14],
            'testing' => ['Pengujian black box', 'Menyusun test case dan menjalankan pengujian tiap modul.', $s['budi'], 'pending', 'medium', 0, 5, 20],
            'laporan' => ['Menyusun laporan akhir proyek', 'Kompilasi dokumentasi dan hasil pengujian.', $s['sari'], 'pending', 'low', 0, 10, 25],
        ];

        $ids = [];

        foreach ($rows as $key => [$title, $desc, $assignee, $status, $priority, $progress, $startOffset, $dueOffset]) {
            $ids[$key] = (int) DB::table('tasks')->insertGetId([
                'project_id' => self::PROJECT_ID,
                'board_id' => $boards[$status],
                'milestone_id' => $milestoneId ?: null,
                'parent_task_id' => null,
                'assigned_to' => $assignee,
                'task_title' => $title,
                'description' => $desc,
                'link' => $key === 'mockup' ? 'https://figma.com/file/demo-hkbp' : null,
                'priority' => $priority,
                'status' => $status,
                'progress_percent' => $progress,
                'start_date' => $now->copy()->addDays($startOffset)->toDateString(),
                'due_date' => $now->copy()->addDays($dueOffset)->toDateString(),
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $tasks
     * @param  array<string, int>  $s
     */
    private function seedTaskDiscussions(array $tasks, array $s, int $angel, Carbon $now): void
    {
        $rows = [
            [$tasks['mockup'], $s['mesya'], 'Warna header-nya samakan dengan logo gereja ya.'],
            [$tasks['mockup'], $s['sari'], 'Sudah kuperbarui, tinggal halaman rekap keuangan.'],
            [$tasks['modul_keuangan'], $angel, 'Pastikan ada validasi agar total persembahan tidak minus.'],
            [$tasks['modul_jemaat'], $s['josua'], 'Import CSV-nya sudah jalan, tapi kolom tanggal lahir masih error.'],
            [$tasks['testing'], $s['budi'], 'Test case menyusul setelah modul jemaat selesai.'],
        ];

        foreach ($rows as $i => [$taskId, $userId, $message]) {
            DB::table('discussions')->insert([
                'project_id' => self::PROJECT_ID,
                'user_id' => $userId,
                'task_id' => $taskId,
                'problem_id' => null,
                'parent_id' => null,
                'message' => $message,
                'created_at' => $now->copy()->subDays(2)->addHours($i),
            ]);

            // View Pelaksanaan mahasiswa membaca komentar dari task_comments.
            DB::table('task_comments')->insert([
                'task_id' => $taskId,
                'comment' => $message,
                'created_at' => $now->copy()->subDays(2)->addHours($i),
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param  array<string, int>  $tasks
     * @param  array<string, int>  $s
     */
    private function seedApprovals(array $tasks, array $s, Carbon $now): void
    {
        // Menunggu persetujuan dosen (muncul di halaman Pelaksanaan dosen).
        DB::table('task_approvals')->insert([
            'project_id' => self::PROJECT_ID,
            'task_id' => $tasks['modul_jemaat'],
            'from_column_key' => 'in_progress',
            'to_column_key' => 'completed',
            'status' => 'pending',
            'checklist_snapshot' => null,
            'note' => null,
            'requested_by' => $s['mesya'],
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at' => $now->copy()->subHours(6),
            'updated_at' => $now->copy()->subHours(6),
        ]);

        // Riwayat: satu disetujui, satu ditolak.
        DB::table('task_approvals')->insert([
            'project_id' => self::PROJECT_ID,
            'task_id' => $tasks['erd'],
            'from_column_key' => 'in_progress',
            'to_column_key' => 'completed',
            'status' => 'approved',
            'checklist_snapshot' => null,
            'note' => null,
            'requested_by' => $s['josua'],
            'reviewed_by' => DB::table('users')->where('email', 'angel@gmail.com')->value('id'),
            'reviewed_at' => $now->copy()->subDays(2),
            'created_at' => $now->copy()->subDays(3),
            'updated_at' => $now->copy()->subDays(2),
        ]);

        DB::table('task_approvals')->insert([
            'project_id' => self::PROJECT_ID,
            'task_id' => $tasks['modul_keuangan'],
            'from_column_key' => 'in_progress',
            'to_column_key' => 'completed',
            'status' => 'rejected',
            'checklist_snapshot' => null,
            'note' => 'Rekap belum diuji dengan data satu bulan penuh.',
            'requested_by' => $s['budi'],
            'reviewed_by' => DB::table('users')->where('email', 'angel@gmail.com')->value('id'),
            'reviewed_at' => $now->copy()->subDay(),
            'created_at' => $now->copy()->subDays(2),
            'updated_at' => $now->copy()->subDay(),
        ]);
    }

    /**
     * @param  array<string, int>  $s
     * @param  array<string, int>  $problems
     */
    private function seedChatAndNotifications(array $s, Carbon $now, array $problems): void
    {
        $messages = [
            [$s['mesya'], 'Halo tim, jangan lupa besok kita demo modul jemaat ke Bu Angel.'],
            [$s['budi'], 'Siap. Aku lanjut rekap persembahan malam ini.'],
            [$s['sari'], 'Mockup halaman keuangan sudah 60%, nanti kukirim linknya.'],
            [$s['josua'], 'ERD sudah disetujui dosen, aku lanjut ke modul penjadwalan.'],
        ];

        foreach ($messages as $i => [$userId, $body]) {
            DB::table('project_messages')->insert([
                'project_id' => self::PROJECT_ID,
                'user_id' => $userId,
                'body' => $body,
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
                'created_at' => $now->copy()->subHours(10 - $i),
                'updated_at' => $now->copy()->subHours(10 - $i),
            ]);
        }

        $notifications = [
            ['angel@gmail.com', 'problem_submitted', 'Masalah baru menunggu review', 'Kelompok 3 mengajukan "Penjadwalan pelayanan dan ibadah sering bentrok" untuk direview.', null],
            ['angel@gmail.com', 'task_approval', 'Permintaan persetujuan tugas', 'Mesya meminta persetujuan penyelesaian tugas "Implementasi modul data jemaat".', null],
            ['mesya@gmail.com', 'problem_reviewed', 'Masalah utama disetujui', 'Dosen menyetujui masalah "Pendataan jemaat dan warta gereja masih manual".', $now->copy()->subDay()],
            ['budi@gmail.com', 'task_rejected', 'Tugas perlu perbaikan', 'Dosen menolak penyelesaian tugas "Implementasi modul persembahan".', null],
        ];

        foreach ($notifications as $i => [$email, $type, $title, $message, $readAt]) {
            DB::table('project_notifications')->insert([
                'project_id' => self::PROJECT_ID,
                'recipient_email' => $email,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'read_at' => $readAt,
                'created_at' => $now->copy()->subHours(12 - $i),
                'updated_at' => $now->copy()->subHours(12 - $i),
            ]);
        }
    }
}
