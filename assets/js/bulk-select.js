function initBulkSelect(tableId, selectAllId, bulkBtnId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const selectAll = document.getElementById(selectAllId);
  const checkboxes = table.querySelectorAll('.row-check');

  function getSelectedIds() {
    return [...checkboxes].filter(c => c.checked).map(c => c.value);
  }

  function syncBulkIds() {
    const ids = getSelectedIds().join(',');
    const bulkIds = document.getElementById('bulkIds');
    const bulkIds2 = document.getElementById('bulkIds2');
    if (bulkIds) bulkIds.value = ids;
    if (bulkIds2) bulkIds2.value = ids;
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
      syncBulkIds();
    });
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      if (selectAll) selectAll.checked = [...checkboxes].every(c => c.checked);
      syncBulkIds();
    });
  });

  const bulkBtn = document.getElementById(bulkBtnId);
  if (bulkBtn) {
    bulkBtn.addEventListener('click', () => {
      const ids = getSelectedIds();
      if (!ids.length) { alert('Pilih minimal satu baris.'); return; }
      if (!confirm('Yakin hapus ' + ids.length + ' data terpilih?')) return;
      syncBulkIds();
      const form = document.getElementById('bulkForm');
      if (form) form.submit();
    });
  }

  document.querySelectorAll('form[data-bulk-sync]').forEach(form => {
    form.addEventListener('submit', (e) => {
      syncBulkIds();
      if (!getSelectedIds().length) {
        e.preventDefault();
        alert('Pilih minimal satu baris.');
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initBulkSelect('dataTable', 'selectAll', 'bulkDeleteBtn');
});
