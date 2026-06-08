(function () {
  const base = (window.APP_BASE || '') + '/api/notifikasi.php';
  const bell = document.getElementById('notifBell');
  const countEl = document.getElementById('notifCount');
  const dropdown = document.getElementById('notifDropdown');

  async function fetchNotif() {
    try {
      const res = await fetch(base + '?action=unread');
      const data = await res.json();
      if (!data.success) return;
      const n = data.unread || 0;
      if (countEl) {
        countEl.textContent = n;
        countEl.style.display = n > 0 ? 'inline' : 'none';
      }
      if (dropdown && data.items) {
        dropdown.innerHTML = data.items.length
          ? data.items.map(i => `<div class="small py-2 border-bottom">${i.pesan}<br><span class="text-muted">${i.created_at}</span></div>`).join('')
          : '<div class="small text-muted">Tidak ada notifikasi baru</div>';
      }
    } catch (e) { /* silent */ }
  }

  if (bell) {
    bell.addEventListener('click', () => {
      if (dropdown) dropdown.classList.toggle('d-none');
      fetch(base + '?action=mark_read', { method: 'POST' });
      if (countEl) countEl.style.display = 'none';
    });
  }

  fetchNotif();
  setInterval(fetchNotif, 30000);
})();
