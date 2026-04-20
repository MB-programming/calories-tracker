(function () {
  const KEY = 'caltrack_theme';

  function applyTheme(mode) {
    document.body.classList.toggle('dark-mode', mode === 'dark');
    const btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.innerHTML = mode === 'dark'
        ? '<i class="bi bi-sun-fill"></i>'
        : '<i class="bi bi-moon-fill"></i>';
      btn.title = mode === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي';
    }
  }

  function toggleTheme() {
    const current = localStorage.getItem(KEY) || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    localStorage.setItem(KEY, next);
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

  const saved = localStorage.getItem(KEY) || 'light';
  if (saved === 'dark') document.body.classList.add('dark-mode');

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { injectButton(); applyTheme(saved); });
  } else {
    injectButton();
    applyTheme(saved);
  }
})();
