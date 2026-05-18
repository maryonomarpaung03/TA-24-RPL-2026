@extends('layouts.app')

@section('title', 'Penilaian Individu - DELPRO')
@section('body_class', 'bg-slate-50 font-sans')
@section('main_class', 'flex-1 overflow-y-auto')
@section('hide_header', '1')

@section('content')
@php
    $groupMembers = $anggota ?? [($studentData['name'] ?? 'Anggota')];
@endphp
<div class="max-w-7xl mx-auto px-6 py-8 space-y-6" x-data="{ activeTab: 'individual' }">
                <!-- Student Header Card -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-start gap-6">
                            <div class="h-16 w-16 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold">
                                {{ substr($studentData['name'], 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h1 class="text-3xl font-bold text-slate-900">{{ $studentData['name'] }}</h1>
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full">{{ $studentData['status'] }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-2">Student ID: {{ $studentData['student_id'] }} • {{ $studentData['department'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700">LAST EVALUATION</p>
                            <p class="text-lg font-bold text-slate-900">{{ $studentData['last_evaluation'] }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-600 font-semibold">PROJECT</p>
                            <p class="text-lg font-bold text-blue-600 mt-1">{{ $studentData['project'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-600 font-semibold">ROLE</p>
                            <p class="text-lg font-bold text-slate-900 mt-1">{{ $studentData['role'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="bg-white rounded-2xl border-b border-slate-200 flex">
                    <button @click="activeTab = 'individual'" :class="activeTab === 'individual' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-600'" class="px-6 py-4 text-sm font-semibold hover:text-slate-900 transition">
                        Individual Evaluation
                    </button>
                    <button @click="activeTab = 'group'" :class="activeTab === 'group' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-600'" class="px-6 py-4 text-sm font-semibold hover:text-slate-900 transition">
                        Group Evaluation
                    </button>
                    <button @click="activeTab = 'lecturer'" :class="activeTab === 'lecturer' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-600'" class="px-6 py-4 text-sm font-semibold hover:text-slate-900 transition">
                        Lecturer Evaluation
                    </button>
                </div>

                <!-- Individual Evaluation -->
                <div x-show="activeTab === 'individual'" x-cloak class="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
                    <!-- Left Panel: Assessment Metrics -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-900">Individual Assessment Metrics</h2>
                        </div>

                        <!-- Assessment Table -->
                        <div class="space-y-4 mb-6">
                            @foreach($assessmentMetrics as $metric)
                            <div class="border-b border-slate-200 pb-4 last:border-b-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $metric['criterion'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm font-bold text-slate-900 w-12 text-right">{{ $metric['score'] }}/100</span>
                                        <span class="px-3 py-1 rounded-full font-bold text-sm" 
                                              :class="'{{ $metric['grade'] }}' === 'A' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'">
                                            {{ $metric['grade'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-600 rounded-full" style="width: {{ $metric['performance'] }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Cumulative Average -->
                        <div class="bg-gradient-to-r from-blue-50 to-slate-50 rounded-xl p-6 border border-blue-200">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-bold text-slate-600 uppercase">CUMULATIVE AVERAGE</p>
                                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $cumulativeAverage }}</p>
                                </div>
                                <div class="text-center border-l border-r border-slate-300">
                                    <p class="text-xs font-bold text-slate-600 uppercase">GRADE</p>
                                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $cumulativeGrade }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-bold text-slate-600 uppercase">STATUS</p>
                                    <p class="text-lg font-bold text-emerald-600 mt-2">{{ $performanceStatus }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Skills & Interactions -->
                    <div class="space-y-6">
                        <!-- Skills Mastery -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">Skills Mastery</h3>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($skillsMastery as $skill)
                                <div class="rounded-lg bg-slate-50 p-4 text-center border border-slate-200">
                                    <p class="text-xs font-semibold text-slate-600 mb-2">{{ $skill['skill'] }}</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ $skill['percentage'] }}%</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- System Interactions -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">System Interactions</h3>
                            <div class="space-y-3">
                                @foreach($systemInteractions as $interaction)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ $interaction['label'] }}</span>
                                    </div>
                                    <span class="text-lg font-bold text-slate-900">{{ $interaction['value'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button type="button" onclick="downloadEvaluationPdf('individual')" class="flex-1 rounded-lg bg-blue-600 text-white font-semibold py-3 hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                <i class="fas fa-file-pdf"></i>
                                Download PDF
                            </button>
                            <button class="flex-1 rounded-lg border border-slate-300 text-slate-700 font-semibold py-3 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                                
                                Share
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Group Evaluation -->
                <div x-show="activeTab === 'group'" x-cloak class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                        <div class="mb-6 flex items-center justify-between gap-3">
                            <h3 class="text-lg font-bold text-slate-900">Group Evaluation</h3>
                        </div>
                        @unless($hasEvaluation ?? false)
                        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Penilaian kelompok belum tersedia. Data akan muncul setelah dosen menilai proyek ini.
                        </div>
                        @endunless
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                                <p class="text-[11px] font-bold text-slate-500 uppercase">Nilai Kelompok</p>
                                <p class="mt-2 text-2xl font-bold text-blue-700">{{ $groupEvaluationSummary['overall_score'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                                <p class="text-[11px] font-bold text-slate-500 uppercase">Grade</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $groupEvaluationSummary['grade'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                                <p class="text-[11px] font-bold text-slate-500 uppercase">Status</p>
                                <p class="mt-2 text-lg font-bold text-emerald-700">{{ $groupEvaluationSummary['status'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                                <p class="text-[11px] font-bold text-slate-500 uppercase">Tanggal Nilai</p>
                                <p class="mt-2 text-sm font-bold text-slate-900">{{ $groupEvaluationSummary['evaluated_at'] ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Komponen</th>
                                        <th class="px-4 py-3 text-left font-semibold">Bobot</th>
                                        <th class="px-4 py-3 text-right font-semibold">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupEvaluationComponents as $component)
                                    <tr class="border-t border-slate-200">
                                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $component['component'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $component['weight'] }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">{{ $component['score'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 mb-4">Nilai per Anggota</h4>
                            <div class="space-y-3">
                                @foreach($groupMemberScores as $member)
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-700">{{ $member['name'] }}</p>
                                    <span class="text-sm font-bold text-blue-700">{{ $member['score'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 mb-3">Catatan Dosen</h4>
                            <p class="text-sm leading-relaxed text-slate-600">{{ $groupLecturerNote }}</p>
                            <p class="mt-4 text-xs font-semibold text-slate-500">Evaluator: {{ $groupEvaluationSummary['evaluator'] ?? '-' }}</p>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" onclick="downloadEvaluationPdf('group')" class="flex-1 rounded-lg bg-blue-600 text-white font-semibold py-3 hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                <i class="fas fa-file-pdf"></i>
                                Download PDF
                            </button>
                            <button class="flex-1 rounded-lg border border-slate-300 text-slate-700 font-semibold py-3 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                                Share
                            </button>
                        </div>
                    </aside>
                </div>

                <!-- Lecturer Evaluation -->
                <div x-show="activeTab === 'lecturer'" x-cloak class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        Detailed Lecturer Feedback
                    </h3>
                    <div class="bg-blue-50 border-l-4 border-blue-600 p-6 rounded-lg italic text-slate-700">
                        {{ $lecturerFeedback }}
                    </div>
                    <p class="text-xs text-slate-500 mt-4 text-center">SUBMITTED 01 OCT 2024</p>
                </div>
 </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
function downloadEvaluationPdf(type) {
    const jsPdfLib = window.jspdf;
    if (!jsPdfLib || !jsPdfLib.jsPDF) {
        alert('Library PDF belum termuat. Silakan coba lagi.');
        return;
    }

    const doc = new jsPdfLib.jsPDF();
    const studentData = @json($studentData);
    const assessmentMetrics = @json($assessmentMetrics);
    const groupSummary = @json($groupEvaluationSummary);
    const groupComponents = @json($groupEvaluationComponents);
    const groupMembers = @json($groupMemberScores);
    const groupNote = @json($groupLecturerNote);

    doc.setFontSize(16);
    doc.text('DELPRO - Evaluation Report', 14, 16);
    doc.setFontSize(11);
    doc.text(`Nama: ${studentData.name}`, 14, 24);
    doc.text(`Project: ${studentData.project}`, 14, 30);
    doc.text(`Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}`, 14, 36);

    if (type === 'individual') {
        doc.setFontSize(13);
        doc.text('Individual Evaluation', 14, 46);

        doc.autoTable({
            startY: 50,
            head: [['Kriteria', 'Skor', 'Grade']],
            body: assessmentMetrics.map((m) => [m.criterion, `${m.score}/100`, m.grade]),
            styles: { fontSize: 10 },
            headStyles: { fillColor: [37, 99, 235] },
        });

        const finalY = doc.lastAutoTable.finalY + 8;
        doc.setFontSize(11);
        doc.text(`Cumulative Average: {{ $cumulativeAverage }}`, 14, finalY);
        doc.text(`Grade: {{ $cumulativeGrade }}`, 14, finalY + 6);
        doc.text(`Status: {{ $performanceStatus }}`, 14, finalY + 12);
        doc.save('individual-evaluation-report.pdf');
        return;
    }

    doc.setFontSize(13);
    doc.text('Group Evaluation', 14, 46);
    doc.setFontSize(11);
    doc.text(`Nilai Kelompok: ${groupSummary.overall_score}`, 14, 54);
    doc.text(`Grade: ${groupSummary.grade}`, 14, 60);
    doc.text(`Status: ${groupSummary.status}`, 14, 66);
    doc.text(`Evaluator: ${groupSummary.evaluator}`, 14, 72);

    doc.autoTable({
        startY: 78,
        head: [['Komponen', 'Bobot', 'Nilai']],
        body: groupComponents.map((c) => [c.component, c.weight, c.score]),
        styles: { fontSize: 10 },
        headStyles: { fillColor: [37, 99, 235] },
    });

    doc.autoTable({
        startY: doc.lastAutoTable.finalY + 8,
        head: [['Anggota', 'Nilai']],
        body: groupMembers.map((m) => [m.name, m.score]),
        styles: { fontSize: 10 },
        headStyles: { fillColor: [30, 64, 175] },
    });

    const noteY = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(11);
    doc.text('Catatan Dosen:', 14, noteY);
    const wrapped = doc.splitTextToSize(groupNote, 180);
    doc.text(wrapped, 14, noteY + 6);
    doc.save('group-evaluation-report.pdf');
}
</script>
@endpush
