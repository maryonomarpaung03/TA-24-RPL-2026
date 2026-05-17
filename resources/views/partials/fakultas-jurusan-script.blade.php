@php
    $prefix = $prefix ?? 'fj';
    $facultyPrograms = $facultyPrograms ?? config('faculties.programs', []);
    $initialFakultas = $initialFakultas ?? null;
    $initialJurusan = $initialJurusan ?? null;
@endphp
<script>
(function () {
    var programsByFaculty = @json($facultyPrograms);
    var fakultasSelect = document.getElementById(@json($prefix . '_fakultas'));
    var jurusanSelect = document.getElementById(@json($prefix . '_jurusan'));
    if (!fakultasSelect || !jurusanSelect) return;

    function fillJurusan(fakultas, selectedJurusan) {
        jurusanSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.selected = !selectedJurusan;
        placeholder.textContent = fakultas ? 'Pilih Jurusan' : 'Pilih fakultas terlebih dahulu';
        jurusanSelect.appendChild(placeholder);

        if (!fakultas || !programsByFaculty[fakultas]) {
            jurusanSelect.disabled = true;
            return;
        }

        (programsByFaculty[fakultas] || []).forEach(function (program) {
            var option = document.createElement('option');
            option.value = program;
            option.textContent = program;
            if (selectedJurusan && selectedJurusan === program) {
                option.selected = true;
                placeholder.selected = false;
            }
            jurusanSelect.appendChild(option);
        });

        jurusanSelect.disabled = false;
    }

    fakultasSelect.addEventListener('change', function () {
        fillJurusan(fakultasSelect.value, null);
    });

    var initialFakultas = @json($initialFakultas);
    var initialJurusan = @json($initialJurusan);
    if (initialFakultas) {
        fakultasSelect.value = initialFakultas;
        fillJurusan(initialFakultas, initialJurusan);
    }

    var form = fakultasSelect.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            if (fakultasSelect.value) {
                jurusanSelect.disabled = false;
            }
        });
    }
})();
</script>
