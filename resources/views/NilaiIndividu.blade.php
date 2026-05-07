<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Individu - DELPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">
        @include('partials.sidebar')

        <main class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
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
                    <button class="px-6 py-4 text-sm font-semibold text-blue-600 border-b-2 border-blue-600 flex items-center gap-2">
                        
                        Individual Evaluation
                    </button>
                    <a href="{{ route('penilaian-kelompok', $id) }}" class="px-6 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-2">
                        
                        Group Evaluation
                    </a>
                    <a href="{{ route('nilai-dari-dosen', $id) }}" class="px-6 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-2">
                        
                        Lecturer Evaluation
                    </a>
                </div>

                <!-- Main Content -->
                <div class="grid gap-6 lg:grid-cols-[1.8fr_1fr]">
                    <!-- Left Panel: Assessment Metrics -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-900">Individual Assessment Metrics</h2>
                            <button class="text-blue-600 font-semibold text-sm flex items-center gap-2 hover:text-blue-700">
                                
                                Edit
                            </button>
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
                            <button class="flex-1 rounded-lg bg-blue-600 text-white font-semibold py-3 hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                
                                Report
                            </button>
                            <button class="flex-1 rounded-lg border border-slate-300 text-slate-700 font-semibold py-3 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                                
                                Share
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lecturer Feedback Section -->
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        
                        Detailed Lecturer Feedback
                    </h3>
                    <div class="bg-blue-50 border-l-4 border-blue-600 p-6 rounded-lg italic text-slate-700">
                        {{ $lecturerFeedback }}
                    </div>
                    <p class="text-xs text-slate-500 mt-4 text-center">SUBMITTED 01 OCT 2024</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
