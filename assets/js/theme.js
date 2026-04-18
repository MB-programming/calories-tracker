(function () {
  const STORAGE_KEY = 'caltrack_theme';

  function applyTheme(mode) {
    document.body.classList.toggle('light-mode', mode === 'light');
    const btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.innerHTML = mode === 'light'
        ? '<i class="bi bi-moon-fill"></i>'
        : '<i class="bi bi-sun-fill"></i>';
      btn.title = mode === 'light' ? 'الوضع الليلي' : 'الوضع النهاري';
    }
  }

  function toggleTheme() {
    const current = localStorage.getItem(STORAGE_KEY) || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem(STORAGE_KEY, next);
    applyTheme(next);
  }

  function injectButton() {
    const userDiv = document.querySelector('.navbar-user');
    if (!userDiv || document.getElementById('theme-toggle')) return;
    const btn = document.createElement('button');
    btn.id = 'theme-toggle';
    btn.onclick = toggleTheme;
    userDiv.insertBefore(btn, userDiv.firstChild);
  }

  const saved = localStorage.getItem(STORAGE_KEY) || 'dark';
  if (saved === 'light') document.body.classList.add('light-mode');

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { injectButton(); applyTheme(saved); });
  } else {
    injectButton();
    applyTheme(saved);
  }
})();
