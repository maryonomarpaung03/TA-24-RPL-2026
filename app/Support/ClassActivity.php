<?php

namespace App\Support;

use App\Models\AcademicClass;
use App\Models\ClassMember;
use App\Models\ClassMessage;
use App\Models\ClassRead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ClassActivity
{
    /**
     * Kelas yang relevan untuk user (dimiliki dosen atau diikuti mahasiswa).
     *
     * @return Collection<int, AcademicClass>
     */
    public static function classesForUser(User $user): Collection
    {
        if (! Schema::hasTable('academic_classes')) {
            return collect();
        }

        $ownedIds = AcademicClass::query()
            ->where('lecturer_id', $user->id)
            ->pluck('id');

        $memberIds = Schema::hasTable('class_members')
            ? ClassMember::query()->where('user_id', $user->id)->pluck('academic_class_id')
            : collect();

        $ids = $ownedIds->merge($memberIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return AcademicClass::query()->whereIn('id', $ids)->get();
    }

    /**
     * Jumlah chat & proyek baru (belum dibaca) untuk satu kelas.
     *
     * @return array{chat: int, projects: int, total: int}
     */
    public static function unreadForClass(AcademicClass $class, User $user): array
    {
        $lastRead = ClassRead::query()
            ->where('user_id', $user->id)
            ->where('academic_class_id', $class->id)
            ->value('last_read_at');

        $chat = 0;
        if (Schema::hasTable('class_messages')) {
            $chat = ClassMessage::query()
                ->where('academic_class_id', $class->id)
                ->where('user_id', '!=', $user->id)
                ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
                ->count();
        }

        $projects = 0;
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'academic_class_id')) {
            $projects = Project::query()
                ->where('academic_class_id', $class->id)
                ->where('created_by', '!=', $user->id)
                ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
                // Dosen tidak dihitung untuk proyek yang masih draft.
                ->when($user->role === 'lecturer', fn ($q) => $q->where('status', '!=', 'draft'))
                ->count();
        }

        return [
            'chat' => $chat,
            'projects' => $projects,
            'total' => $chat + $projects,
        ];
    }

    /**
     * Ringkasan unread untuk semua kelas user.
     *
     * @return array{total: int, by_class: array<int, array{chat: int, projects: int, total: int}>}
     */
    public static function summary(User $user): array
    {
        $byClass = [];
        $total = 0;

        foreach (self::classesForUser($user) as $class) {
            $unread = self::unreadForClass($class, $user);
            $byClass[$class->id] = $unread;
            $total += $unread['total'];
        }

        return ['total' => $total, 'by_class' => $byClass];
    }

    /**
     * Tandai semua kelas milik/diikuti user sudah dibaca.
     */
    public static function markAllRead(User $user): void
    {
        foreach (self::classesForUser($user) as $class) {
            self::markRead((int) $user->id, (int) $class->id);
        }
    }

    /**
     * Tandai kelas sudah dibaca sampai sekarang.
     */
    public static function markRead(int $userId, int $classId): void
    {
        if (! Schema::hasTable('class_reads')) {
            return;
        }

        ClassRead::query()->updateOrCreate(
            ['user_id' => $userId, 'academic_class_id' => $classId],
            ['last_read_at' => now()]
        );
    }
}
