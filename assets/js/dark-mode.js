(function () {
  const root = document.documentElement;
  const toggle = document.getElementById('darkModeToggle');
  const saved = localStorage.getItem('siakad-theme') || 'light';
  root.setAttribute('data-theme', saved);
  if (toggle) toggle.checked = saved === 'dark';

  function updateClock() {
    const now = new Date();
    const clock = document.getElementById('liveClock');
    const date = document.getElementById('liveDate');
    if (clock) clock.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    if (date) date.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }
  updateClock();
  setInterval(updateClock, 1000);

  if (toggle) {
    toggle.addEventListener('change', () => {
      const theme = toggle.checked ? 'dark' : 'light';
      root.setAttribute('data-theme', theme);
      localStorage.setItem('siakad-theme', theme);
    });
  }
})();
