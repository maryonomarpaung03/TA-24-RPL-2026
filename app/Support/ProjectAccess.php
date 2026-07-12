<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectAccess
{
    public static function userCanAccess(User|int $user, Project|int $project): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        $projectId = $project instanceof Project ? $project->id : $project;

        $row = Project::query()->find($projectId);

        if (! $row) {
            return false;
        }

        if ((int) $row->created_by === (int) $userId) {
            return true;
        }

        return ProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function lecturerCanView(User|int $user, Project|int $project): bool
    {
        $email = strtolower(trim($user instanceof User ? (string) $user->email : ''));
        $row = $project instanceof Project ? $project : Project::query()->find($project);

        if (! $row || $email === '') {
            return false;
        }

        return strtolower(trim((string) $row->lecturer_email)) === $email;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function projectsForUser(int $userId): array
    {
        $ownedIds = Project::query()
            ->where('created_by', $userId)
            ->pluck('id');

        $memberIds = ProjectMember::query()
            ->where('user_id', $userId)
            ->pluck('project_id');

        $ids = $ownedIds->merge($memberIds)->unique()->values();

        return Project::query()
            ->whereIn('id', $ids)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $p) => self::toSelectedArray($p))
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveSelected(int|string|null $projectId, int $userId): ?array
    {
        if ($projectId === null || $projectId === '') {
            return null;
        }

        $project = Project::query()->find((int) $projectId);

        if (! $project) {
            return null;
        }

        if (! self::userCanAccess($userId, $project)) {
            return null;
        }

        return self::toSelectedArray($project);
    }

    /**
     * @return array<string, mixed>
     */
    public static function toSelectedArray(Project $project): array
    {
        $status = $project->status ?? 'draft';

        return [
            'id' => $project->id,
            'name' => $project->title,
            'description' => self::displayDescription($project->description, 160),
            'status' => $status,
            'lecturer_email' => $project->lecturer_email,
            'lecturer_name' => $project->lecturer_name,
            'group_name' => $project->group_name,
            'course_name' => $project->course_name,
            'planned_months' => $project->planned_months,
            'submitted_at' => $project->submitted_at,
            'is_approved' => in_array($status, ['active', 'completed', 'pending_final_review', 'pending_final_revision'], true),
            'is_pending' => in_array($status, ['pending_approval', 'pending_revision'], true),
            'is_draft' => $status === 'draft',
            'can_access_pjbl' => self::canAccessPjbl($status),
            'is_under_review' => in_array($status, ['pending_approval', 'pending_revision'], true),
            'is_in_review' => in_array($status, ['pending_approval', 'pending_revision'], true),
            'is_pending_revision' => $status === 'pending_revision',
            'is_locked' => self::isFinalized($status),
            'status_label' => match ($status) {
                'draft' => 'Draft',
                'pending_approval' => 'In Review',
                'pending_revision' => 'Review Perubahan',
                'active' => 'In Progress',
                'pending_final_review' => 'Menunggu Penilaian',
                'pending_final_revision' => 'Revisi Finalisasi',
                'completed' => 'Done',
                'rejected' => 'Rejected',
                'archived' => 'Archived',
                default => 'Planning',
            },
            'created_by' => (int) $project->created_by,
        ];
    }

    public static function canAccessPjbl(?string $status): bool
    {
        return in_array($status, [
            'active',
            'completed',
            'pending_revision',
            'pending_final_review',
            'pending_final_revision',
        ], true);
    }

    /**
     * Proyek sudah dikirim final ke dosen: seluruh papan tugas dikunci
     * (hanya bisa dilihat & dikomentari) sampai dosen membuka revisi.
     */
    public static function isFinalized(?string $status): bool
    {
        return in_array($status, ['pending_final_review', 'completed'], true);
    }

    public static function isProjectManager(Project $project, int $userId): bool
    {
        return (int) $project->created_by === $userId;
    }

    /**
     * @return list<string>
     */
    public static function editableStatuses(): array
    {
        return ['draft', 'active', 'pending_approval', 'pending_revision', 'completed', 'rejected'];
    }

    /**
     * Semua status proyek yang boleh dilihat dosen di halaman pemantauan.
     *
     * @return list<string>
     */
    public static function lecturerVisibleStatuses(): array
    {
        return ['active', 'completed', 'pending_final_review', 'pending_final_revision'];
    }

    /**
     * @return array{deskripsi: string, masalah: string}
     */
    public static function parseProjectDescription(?string $description): array
    {
        $description = (string) $description;

        if (preg_match('/\s*---\s*Masalah utama\s*---\s*/iu', $description, $match, PREG_OFFSET_CAPTURE)) {
            $deskripsi = substr($description, 0, $match[0][1]);
            $masalah = substr($description, $match[0][1] + strlen($match[0][0]));
        } else {
            $deskripsi = $description;
            $masalah = '';
        }

        $deskripsi = self::stripAttachmentMarkers($deskripsi);
        $masalah = self::stripAttachmentMarkers($masalah);

        return [
            'deskripsi' => $deskripsi,
            'masalah' => $masalah,
        ];
    }

    public static function stripAttachmentMarkers(string $text): string
    {
        return trim(preg_replace('/\s*\[Lampiran:[^\]]+\]/iu', '', $text) ?? $text);
    }

    /**
     * Teks deskripsi proyek untuk ditampilkan di UI (tanpa blok masalah & marker lampiran).
     */
    public static function displayDescription(?string $description, int $limit = 0): string
    {
        $text = self::parseProjectDescription($description)['deskripsi'];

        if ($text === '') {
            return 'Belum ada deskripsi proyek.';
        }

        return $limit > 0 ? Str::limit($text, $limit) : $text;
    }

    /**
     * Format penyimpanan standar: deskripsi + pemisah + masalah utama (tanpa URL lampiran).
     */
    public static function formatStoredDescription(string $deskripsi, string $masalah): string
    {
        $deskripsi = self::stripAttachmentMarkers(trim($deskripsi));
        $masalah = self::stripAttachmentMarkers(trim($masalah));

        if ($masalah === '') {
            return $deskripsi;
        }

        return $deskripsi."\n\n--- Masalah utama ---\n".$masalah;
    }

    /**
     * Simpan lampiran proyek ke disk public; kembalikan path relatif untuk kolom logo.
     */
    public static function storeProjectAttachment(
        \Illuminate\Http\Request $request,
        ?string $existingPath = null
    ): ?string {
        $file = null;

        if ($request->hasFile('lampiran')) {
            $files = $request->file('lampiran');
            $file = is_array($files) ? ($files[0] ?? null) : $files;
        } elseif ($request->hasFile('lampiran.0')) {
            $file = $request->file('lampiran.0');
        }

        if (! $file || ! $file->isValid()) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $file->store('project_logos', 'public');
    }

    public static function projectAttachmentUrl(?string $logo, ?string $description): ?string
    {
        if ($logo) {
            return self::resolvePublicUrl($logo);
        }

        return self::resolvePublicUrl(self::extractAttachmentUrl($description));
    }

    /**
     * @return list<string>
     */
    public static function memberInitials(int $projectId): array
    {
        $rows = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $projectId)
            ->select('users.full_name', 'users.name')
            ->orderBy('project_members.id')
            ->get();

        if ($rows->isEmpty()) {
            $creator = DB::table('users')
                ->join('projects', 'projects.created_by', '=', 'users.id')
                ->where('projects.id', $projectId)
                ->select('users.full_name', 'users.name')
                ->first();

            if ($creator) {
                return [self::initialsFromName($creator->full_name ?: $creator->name)];
            }

            return [];
        }

        return $rows
            ->map(fn ($row) => self::initialsFromName($row->full_name ?: $row->name))
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * Semua peserta projek ditampilkan sebagai "Anggota"; label ini murni untuk
     * tampilan dan tidak memengaruhi hak akses (lihat isProjectManager()).
     *
     * @return list<array{initials: string, name: string, role: string}>
     */
    public static function teamMembersForProject(int $projectId): array
    {
        $members = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->where('project_members.project_id', $projectId)
            ->select('users.full_name', 'users.name', 'project_members.user_id')
            ->orderBy('project_members.id')
            ->get()
            ->map(fn ($row) => [
                'initials' => self::initialsFromName($row->full_name ?: $row->name),
                'name' => $row->full_name ?: $row->name ?: 'Anggota',
                'role' => 'Anggota',
            ])
            ->values()
            ->all();

        if ($members !== []) {
            return $members;
        }

        $creator = DB::table('users')
            ->join('projects', 'projects.created_by', '=', 'users.id')
            ->where('projects.id', $projectId)
            ->select('users.full_name', 'users.name')
            ->first();

        if ($creator) {
            return [[
                'initials' => self::initialsFromName($creator->full_name ?: $creator->name),
                'name' => $creator->full_name ?: $creator->name,
                'role' => 'Anggota',
            ]];
        }

        return [];
    }

    public static function initialsFromName(?string $display): string
    {
        $display = trim((string) $display);
        $words = preg_split('/\s+/', $display, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return 'U';
    }

    public static function extractAttachmentUrl(?string $description): ?string
    {
        if (preg_match('/\[Lampiran: ([^\]]+)\]/', (string) $description, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    public static function resolvePublicUrl(?string $pathOrUrl): ?string
    {
        $relativePath = self::normalizePublicStoragePath($pathOrUrl);

        if ($relativePath === null) {
            return null;
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return '/storage/'.$relativePath;
    }

    /**
     * Path relatif di disk public (mis. project_logos/file.png), atau null jika tidak valid.
     */
    public static function normalizePublicStoragePath(?string $pathOrUrl): ?string
    {
        if ($pathOrUrl === null || trim($pathOrUrl) === '') {
            return null;
        }

        $value = trim($pathOrUrl);

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH) ?? '';
            if (! is_string($path) || ! str_contains($path, '/storage/')) {
                return null;
            }

            $value = substr($path, (int) strpos($path, '/storage/') + strlen('/storage/'));
        }

        $value = ltrim($value, '/');

        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        return $value !== '' ? $value : null;
    }

    public static function isImageUrl(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $path);
    }

    /**
     * @return array{preview_url: ?string, attachment_url: ?string, attachment_kind: ?string, has_media: bool}
     */
    public static function projectMediaPreview(?string $logo, ?string $description): array
    {
        $attachmentUrl = self::projectAttachmentUrl($logo, $description);
        $logoUrl = self::resolvePublicUrl($logo);

        foreach ([$attachmentUrl, $logoUrl] as $url) {
            if ($url && self::isImageUrl($url)) {
                return [
                    'preview_url' => $url,
                    'attachment_url' => $attachmentUrl ?? $logoUrl,
                    'attachment_kind' => 'image',
                    'has_media' => true,
                ];
            }
        }

        $fileUrl = $attachmentUrl ?? $logoUrl;

        if ($fileUrl) {
            $path = parse_url($fileUrl, PHP_URL_PATH) ?? $fileUrl;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return [
                'preview_url' => null,
                'attachment_url' => $fileUrl,
                'attachment_kind' => in_array($ext, ['pdf'], true) ? 'pdf' : 'file',
                'has_media' => true,
            ];
        }

        return [
            'preview_url' => null,
            'attachment_url' => null,
            'attachment_kind' => null,
            'has_media' => false,
        ];
    }

    public static function shortDescription(?string $description, int $limit = 120): string
    {
        return self::displayDescription($description, $limit);
    }
}
