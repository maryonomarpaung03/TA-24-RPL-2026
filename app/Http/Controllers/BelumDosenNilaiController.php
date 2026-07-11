<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use App\Support\PjblContext;
use App\Support\ProjectCatalog;

class BelumDosenNilaiController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index($id)
    {
        $selected = ProjectCatalog::find($id);

        if (! $selected) {
            return redirect()
                ->route('my-project')
                ->with('error', 'Proyek tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Kalau dosen sudah menilai, langsung tampilkan hasilnya.
        if ($this->evaluations->lecturerEvaluation((int) $id)) {
            return redirect()->route('nilai-dari-dosen', $id);
        }

        return view('BelumDosenNilai', [
            'user' => PjblContext::viewer(),
            'namaProjek' => $selected['name'],
            'id' => (int) $id,
        ]);
    }
}
