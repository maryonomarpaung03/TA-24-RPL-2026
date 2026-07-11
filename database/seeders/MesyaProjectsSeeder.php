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

/**
 * 15 proyek untuk akun mesya@gmail.com - tiap proyek beda dosen, beda kelas,
 * beda kelompok, dan beda status. Idempotent: proyek dicari dari judul.
 */
class MesyaProjectsSeeder extends Seeder
{
    /** [judul, masalah utama, nama kelompok, email dosen, status, mesya jadi PM?] */
    private const PROJECTS = [
        ['Sistem Informasi Akademik Terpadu', 'Data akademik tersebar di beberapa aplikasi sehingga rekap nilai lambat.', 'Kelompok Alpha', 'angel@gmail.com', 'active', true],
        ['Aplikasi Tracking Skripsi Mahasiswa', 'Progres bimbingan skripsi sulit dipantau dosen maupun mahasiswa.', 'Kelompok Beta', 'rina@gmail.com', 'active', true],
        ['Portal Alumni & Tracer Study', 'Data alumni tidak terkumpul sehingga tracer study berjalan lambat.', 'Kelompok Gamma', 'hendra@gmail.com', 'active', false],
        ['Sistem Reservasi Laboratorium', 'Jadwal pemakaian laboratorium sering bentrok antar kelas.', 'Kelompok Delta', 'tigor@gmail.com', 'active', true],
        ['Aplikasi Koperasi Simpan Pinjam', 'Pencatatan simpan pinjam koperasi kampus masih memakai buku besar.', 'Kelompok Epsilon', 'marta@gmail.com', 'active', false],
        ['Dashboard Monitoring Beasiswa', 'Penerima beasiswa tidak terpantau apakah masih memenuhi syarat.', 'Kelompok Zeta', 'fernando@gmail.com', 'active', true],
        ['Sistem Manajemen Aset Kampus', 'Aset kampus berpindah tanpa catatan sehingga sulit diinventarisasi.', 'Kelompok Eta', 'yohana@gmail.com', 'active', false],
        ['Aplikasi E-Voting Organisasi Kampus', 'Pemilihan ketua organisasi masih manual dan rawan kecurangan.', 'Kelompok Theta', 'parlin@gmail.com', 'active', true],
        ['Platform Konsultasi Akademik Daring', 'Mahasiswa sulit menjadwalkan konsultasi dengan dosen wali.', 'Kelompok Iota', 'rosa@gmail.com', 'active', false],
        ['Sistem Pengaduan Layanan Kampus', 'Pengaduan mahasiswa tidak terdokumentasi dan lambat ditindaklanjuti.', 'Kelompok Kappa', 'mose@gmail.com', 'active', true],
        ['Aplikasi Manajemen Kegiatan UKM', 'Kegiatan UKM tumpang tindih karena tidak ada kalender bersama.', 'Kelompok Lambda', 'angel@gmail.com', 'completed', false],
        ['Sistem Rekomendasi Mata Kuliah Pilihan', 'Mahasiswa bingung memilih mata kuliah pilihan yang sesuai minat.', 'Kelompok Mu', 'rina@gmail.com', 'completed', true],
        ['Aplikasi Peminjaman Kendaraan Dinas', 'Peminjaman kendaraan dinas kampus dicatat manual di pos satpam.', 'Kelompok Nu', 'marta@gmail.com', 'pending_approval', true],
        ['Sistem Digitalisasi Arsip Fakultas', 'Arsip surat fakultas menumpuk dan sulit dicari saat dibutuhkan.', 'Kelompok Xi', 'hendra@gmail.com', 'pending_approval', false],
        ['Aplikasi Katalog Penelitian Dosen', 'Publikasi penelitian dosen belum terkumpul dalam satu katalog.', 'Kelompok Omicron', 'fernando@gmail.com', 'draft', true],
    ];

    private const TASK_TEMPLATES = [
        ['Analisis kebutuhan & wawancara pengguna', 'completed', 'high'],
        ['Menyusun dokumen SRS', 'completed', 'high'],
        ['Perancangan basis data', 'completed', 'medium'],
        ['Perancangan antarmuka (mockup)', 'in_progress', 'medium'],
        ['Implementasi modul utama', 'in_progress', 'high'],
        ['Implementasi modul pendukung', 'pending', 'medium'],
        ['Pengujian & perbaikan bug', 'pending', 'medium'],
        ['Penyusunan laporan akhir', 'pending', 'low'],
    ];

    public function __construct(
        private readonly ProjectWorkspaceService $workspace,
        private readonly ProjectTaskService $tasks,
        private readonly EvaluationService $evaluations
    ) {}

    public function run(): void
    {
        $now = Carbon::parse('2026-07-11 11:00:00');

        $mesya = User::query()->where('email', 'mesya@gmail.com')->first();

        if (! $mesya) {
            $this->command->error('User mesya@gmail.com tidak ditemukan.');

            return;
        }

        // Kolam rekan satu tim (mahasiswa selain Mesya).
        $pool = User::query()
            ->where('role', 'student')
            ->where('id', '!=', $mesya->id)
            ->orderBy('id')
            ->get()
            ->all();

        $created = 0;

        foreach (self::PROJECTS as $i => [$title, $problem, $groupName, $lecturerEmail, $status, $mesyaIsPm]) {
            if (Project::query()->where('name', $title)->exists()) {
                continue;
            }

            $lecturer = User::query()->where('email', $lecturerEmail)->first();

            if (! $lecturer) {
                continue;
            }

            $class = DB::table('academic_classes')->where('lecturer_id', $lecturer->id)->first();

            // Tiga rekan tim berbeda untuk tiap proyek.
            $mates = [
                $pool[($i * 3) % count($pool)],
                $pool[($i * 3 + 1) % count($pool)],
                $pool[($i * 3 + 2) % count($pool)],
            ];

            $creator = $mesyaIsPm ? $mesya : $mates[0];
            $members = $mesyaIsPm
                ? $mates
                : [$mesya, $mates[1], $mates[2]];

            $project = Project::query()->create([
                'name' => $title,
                'academic_class_id' => $class->id ?? null,
                'group_name' => $groupName,
                'course_name' => $class->course_name ?? 'Proyek Perangkat Lunak',
                'description' => ProjectAccess::formatStoredDescription(
                    'Proyek '.($class->course_name ?? 'kolaborasi').' bersama '.$groupName.'.',
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
                array_map(fn (User $u) => $u->email, $members)
            );
            $this->tasks->ensureColumns((int) $project->id);

            // Mesya ikut terdaftar di kelas proyek tersebut.
            if ($class) {
                DB::table('class_members')->updateOrInsert(
                    ['academic_class_id' => $class->id, 'user_id' => $mesya->id],
                    ['joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            if (in_array($status, ['active', 'completed'], true)) {
                $team = array_merge([$creator], $members);
                $this->fillProject($project, $team, $lecturer, $problem, $now);
            }

            $created++;
        }

        $this->command->info($created.' proyek baru dibuat untuk mesya@gmail.com.');
    }

    /** @param list<User> $team */
    private function fillProject(Project $project, array $team, User $lecturer, string $problem, Carbon $now): void
    {
        $projectId = (int) $project->id;
        $teamIds = array_values(array_unique(array_map(fn (User $u) => $u->id, $team)));

        $this->fillProblems($projectId, $teamIds, $problem, $now);
        $taskIds = $this->fillTasks($projectId, $teamIds, $project->name, $now);
        $this->fillComments($projectId, $taskIds, $teamIds, $lecturer->id, $now);
        $this->fillChat($projectId, $teamIds, $now);

        if ($project->status === 'completed') {
            $this->fillEvaluation($projectId, $lecturer->id, $team, $now);
        }
    }

    private function fillProblems(int $projectId, array $teamIds, string $problem, Carbon $now): void
    {
        $rows = [
            [$teamIds[0], $problem, 'Kebutuhan Proyek', 'Tinggi', 'done', 0, 'Rumusan masalah sudah baik, lanjutkan ke dekomposisi.'],
            [$teamIds[1] ?? $teamIds[0], 'Proses manual menyita banyak waktu staf', 'Diskusi', 'Sedang', 'submitted', 0, null],
            [$teamIds[2] ?? $teamIds[0], 'Belum ada standar data yang seragam', 'Teknik', 'Sedang', 'voting', 1, null],
        ];

        foreach ($rows as $i => [$by, $title, $category, $priority, $status, $votingOpen, $feedback]) {
            $problemId = DB::table('problem_identifications')->insertGetId([
                'project_id' => $projectId,
                'created_by' => $by,
                'title' => $title,
                'description' => $title.'. Perlu dianalisis bersama tim.',
                'category' => $category,
                'priority' => $priority,
                'board_status' => $status,
                'voting_open' => $votingOpen,
                'voting_deadline' => $votingOpen ? $now->copy()->addDays(3) : null,
                'lecturer_feedback' => $feedback,
                'created_at' => $now->copy()->subDays(20 - $i * 4),
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

    /** @return list<int> */
    private function fillTasks(int $projectId, array $teamIds, string $projectName, Carbon $now): array
    {
        $boards = [];

        foreach ([
            'pending' => ['Belum Dikerjakan', 1, false],
            'in_progress' => ['Sedang Dikerjakan', 2, false],
            'completed' => ['Selesai', 3, true],
        ] as $key => [$name, $position, $isCompleted]) {
            $boards[$key] = (int) DB::table('project_boards')->insertGetId([
                'project_id' => $projectId,
                'name' => $name,
                'position' => $position,
                'is_completed' => $isCompleted,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $milestoneId = (int) DB::table('milestones')->where('project_id', $projectId)->value('id');
        $ids = [];

        foreach (self::TASK_TEMPLATES as $i => [$title, $status, $priority]) {
            $ids[] = (int) DB::table('tasks')->insertGetId([
                'project_id' => $projectId,
                'board_id' => $boards[$status],
                'milestone_id' => $milestoneId ?: null,
                'assigned_to' => $teamIds[$i % count($teamIds)],
                'task_title' => $title,
                'description' => $title.' untuk proyek '.$projectName.'.',
                'priority' => $priority,
                'status' => $status,
                'progress_percent' => match ($status) {
                    'completed' => 100,
                    'in_progress' => 45 + ($i * 9) % 40,
                    default => 0,
                },
                'start_date' => $now->copy()->subDays(20 - $i)->toDateString(),
                'due_date' => $now->copy()->addDays($i * 4 - 6)->toDateString(),
                'created_at' => $now->copy()->subDays(22),
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    private function fillComments(int $projectId, array $taskIds, array $teamIds, int $lecturerId, Carbon $now): void
    {
        $messages = [
            'Bagian ini sudah selesai, silakan direview.',
            'Ada kendala kecil di validasi input, sedang kuperbaiki.',
            'Tolong konsisten dengan rancangan basis data ya.',
        ];

        foreach (array_slice($taskIds, 0, 3) as $i => $taskId) {
            $userId = $i === 2 ? $lecturerId : $teamIds[$i % count($teamIds)];

            DB::table('discussions')->insert([
                'project_id' => $projectId,
                'user_id' => $userId,
                'task_id' => $taskId,
                'problem_id' => null,
                'parent_id' => null,
                'message' => $messages[$i],
                'created_at' => $now->copy()->subDays(4)->addHours($i),
            ]);

            DB::table('task_comments')->insert([
                'task_id' => $taskId,
                'comment' => $messages[$i],
                'created_at' => $now->copy()->subDays(4)->addHours($i),
                'updated_at' => $now,
            ]);
        }
    }

    private function fillChat(int $projectId, array $teamIds, Carbon $now): void
    {
        $messages = [
            'Halo tim, ini kanal diskusi proyek kita.',
            'Modul utama sudah kupush ke repositori.',
            'Besok kita demo ke dosen pembimbing ya.',
        ];

        foreach ($messages as $i => $body) {
            DB::table('project_messages')->insert([
                'project_id' => $projectId,
                'user_id' => $teamIds[$i % count($teamIds)],
                'body' => $body,
                'created_at' => $now->copy()->subDays(2)->addHours($i * 3),
                'updated_at' => $now->copy()->subDays(2)->addHours($i * 3),
            ]);
        }
    }

    /** @param list<User> $team */
    private function fillEvaluation(int $projectId, int $lecturerId, array $team, Carbon $now): void
    {
        $components = [];
        foreach (array_keys(EvaluationService::COMPONENTS) as $i => $key) {
            $components[$key] = 84 + ($i * 3) % 10;
        }

        $students = [];
        foreach ($team as $i => $member) {
            $base = 82 + ($i * 4) % 14;

            $criteria = [];
            foreach (array_keys(EvaluationService::CRITERIA) as $j => $key) {
                $criteria[$key] = min(100, $base + ($j * 3) % 9);
            }

            $students[$member->id] = [
                'score' => $base,
                'feedback' => 'Kontribusi '.$member->full_name.' pada proyek ini baik. Pertahankan.',
                'criteria' => $criteria,
            ];
        }

        $this->evaluations->saveLecturerEvaluation(
            $projectId,
            $lecturerId,
            88,
            $components,
            'Proyek diselesaikan tepat waktu dengan dokumentasi lengkap.',
            $students
        );

        foreach ($team as $i => $evaluator) {
            $scores = [];
            foreach ($team as $j => $member) {
                $scores[$member->id] = 84 + (($i + $j) * 3) % 13;
            }

            $this->evaluations->savePeerEvaluation(
                $projectId,
                $evaluator->id,
                87 + ($i * 2) % 7,
                $scores,
                'Kolaborasi tim berjalan lancar sepanjang proyek.'
            );
        }
    }
}
