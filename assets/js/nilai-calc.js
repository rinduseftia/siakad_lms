function konversiGrade(nilai) {
  if (nilai >= 85) return 'A';
  if (nilai >= 70) return 'B';
  if (nilai >= 55) return 'C';
  if (nilai >= 40) return 'D';
  return 'E';
}

function hitungNilaiAkhir(tugas, quiz, uts, uas) {
  return Math.round((tugas * 0.2 + quiz * 0.2 + uts * 0.3 + uas * 0.3) * 100) / 100;
}

function initNilaiForm(form) {
  const fields = ['tugas', 'quiz', 'uts', 'uas'];
  const outAkhir = form.querySelector('.nilai-akhir-preview');
  const outGrade = form.querySelector('.grade-preview');

  function update() {
    const vals = fields.map(f => parseFloat(form.querySelector('[name="' + f + '"]')?.value) || 0);
    const akhir = hitungNilaiAkhir(...vals);
    const grade = konversiGrade(akhir);
    if (outAkhir) outAkhir.textContent = akhir;
    if (outGrade) outGrade.textContent = grade;
    const hiddenAkhir = form.querySelector('[name="nilai_akhir"]');
    const hiddenGrade = form.querySelector('[name="grade"]');
    if (hiddenAkhir) hiddenAkhir.value = akhir;
    if (hiddenGrade) hiddenGrade.value = grade;
  }

  fields.forEach(f => {
    const el = form.querySelector('[name="' + f + '"]');
    if (el) el.addEventListener('input', update);
  });
  update();
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.form-nilai, #formNilai').forEach(initNilaiForm);
});
