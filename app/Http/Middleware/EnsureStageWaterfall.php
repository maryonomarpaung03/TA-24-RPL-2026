<?php

namespace App\Http\Middleware;

use App\Services\StageProgressService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penegakan waterfall di sisi server. Tab yang terkunci sudah tidak bisa diklik
 * di UI, tapi tanpa ini URL tahapan berikutnya masih bisa diketik langsung.
 */
class EnsureStageWaterfall
{
    public function __construct(private readonly StageProgressService $stages) {}

    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $request->route('id');
        $stage = StageProgressService::stageForRoute($request->route()?->getName());

        if ($projectId === null || $stage === null) {
            return $next($request);
        }

        $projectId = (int) $projectId;

        if (! $this->stages->canAccess($projectId, $stage)) {
            $current = $this->stages->currentStage($projectId);

            return redirect()
                ->route(StageProgressService::definitions()[$current]['route'], $projectId)
                ->with('stage_locked', 'Tahapan '.StageProgressService::label($current)
                    .' belum diselesaikan. Selesaikan tahapan tersebut sebelum membuka '
                    .StageProgressService::label($stage).'.');
        }

        // Tahap yang sudah difinalisasi tetap boleh dibuka, tapi hanya untuk dibaca.
        if ($request->method() !== 'GET' && $this->stages->isFinalized($projectId, $stage)) {
            $message = 'Tahapan '.StageProgressService::label($stage)
                .' sudah difinalisasi dan tidak dapat diubah. Ajukan perbaikan ke dosen bila perlu mengubahnya.';

            // Kanvas dekomposisi memanggil endpoint tahap lewat fetch(). Redirect HTML
            // membuat res.json() di klien gagal dan tampil sebagai "kesalahan koneksi",
            // jadi permintaan JSON dijawab JSON.
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'locked' => true,
                    'message' => $message,
                ], 423);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
