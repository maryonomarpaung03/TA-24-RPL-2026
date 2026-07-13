<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\FinalizationService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FinalisasiController extends Controller
{
    public function __construct(private readonly FinalizationService $finalization) {}

    /** Kirim finalisasi proyek ke dosen dari tahapan Assessment & Reflection. */
    public function submit(Request $request, int $id)
    {
        $project = Project::query()->findOrFail($id);
        $userId = (int) Auth::id();

        if (! ProjectAccess::userCanAccess($userId, $project)) {
            abort(403, 'Anda bukan anggota proyek ini.');
        }

        $validated = $request->validate([
            'report_type' => 'required|in:file,link',
            'report' => [
                'nullable',
                'required_if:report_type,file',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx',
            ],
            'report_link' => 'nullable|required_if:report_type,link|url|max:500',
            'presentation_link' => 'nullable|url|max:500',
            'repo_link' => 'nullable|url|max:500',
            'summary' => 'required|string|min:20|max:2000',
            'confirm_comments' => 'accepted',
            'confirm_data' => 'accepted',
            'confirm_final' => 'accepted',
        ], [
            'report.required_if' => 'Berkas laporan akhir wajib diunggah bila Anda memilih unggah berkas.',
            'report.mimes' => 'Laporan akhir harus berupa PDF atau DOC/DOCX.',
            'report.max' => 'Ukuran laporan akhir maksimal 10 MB.',
            'report_link.required_if' => 'Link laporan akhir wajib diisi bila Anda memilih tautan.',
            'summary.min' => 'Ringkasan hasil proyek minimal 20 karakter.',
            'confirm_comments.accepted' => 'Semua pernyataan finalisasi wajib dicentang.',
            'confirm_data.accepted' => 'Semua pernyataan finalisasi wajib dicentang.',
            'confirm_final.accepted' => 'Semua pernyataan finalisasi wajib dicentang.',
        ]);

        try {
            $this->finalization->submit(
                $project,
                $userId,
                $validated,
                $request->file('report')
            );
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()
            ->route('penilaian-individu', $project->id)
            ->with('success', 'Finalisasi proyek berhasil dikirim ke dosen. Proyek kini terkunci sampai dosen menilai.');
    }
}
