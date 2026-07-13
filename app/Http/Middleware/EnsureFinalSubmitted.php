<?php

namespace App\Http\Middleware;

use App\Services\FinalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman nilai baru boleh dibuka setelah tim mengirim finalisasi proyek.
 * Sebelum itu, tim diarahkan kembali ke halaman refleksi & penilaian antar anggota.
 */
class EnsureFinalSubmitted
{
    public function __construct(private readonly FinalizationService $finalization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $request->route('id');

        if ($projectId === null) {
            return $next($request);
        }

        if ($this->finalization->latestSubmission((int) $projectId)) {
            return $next($request);
        }

        return redirect()
            ->route('penilaian-individu', (int) $projectId)
            ->with('stage_locked', 'Kirim finalisasi proyek terlebih dahulu. Nilai dari dosen baru dapat dilihat setelah laporan akhir dikirim.');
    }
}
