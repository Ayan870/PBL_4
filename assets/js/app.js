/* ===== app.js — Dashboard Common Logic ===== */

document.addEventListener('DOMContentLoaded', function () {
  requireAuth();
  loadUserInfo();
  animateStats();
  highlightActiveNav();
});

/* --- Load user info from session --- */
function loadUserInfo() {
  const name = sessionStorage.getItem('pbl_name') || 'User';
  const role = sessionStorage.getItem('pbl_role') || '';

  const nameEl = document.getElementById('userName');
  if (nameEl) nameEl.textContent = name;

  const greetEl = document.getElementById('greetName');
  if (greetEl) greetEl.textContent = name.split(' ')[0];

  const avatarEl = document.getElementById('userAvatar');
  if (avatarEl) {
    const initials = name.split(' ').map(p => p[0]).join('').substring(0, 2).toUpperCase();
    avatarEl.textContent = initials || 'U';
  }

  const roleLabel = document.getElementById('userRoleLabel');
  if (roleLabel) {
    const labels = { student: 'Student', supervisor: 'Supervisor', manager: 'PBL Manager' };
    roleLabel.textContent = labels[role] || role;
  }
}

/* --- Logout --- */
function logout() {
  sessionStorage.clear();
  // Always go back to root index
  const depth = window.location.pathname.split('/').filter(Boolean).length - 1;
  const root  = '../'.repeat(Math.max(depth - 1, 0));
  window.location.href = root + 'index.html';
}

/* --- Highlight active nav link --- */
function highlightActiveNav() {
  const current = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar .nav-link').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href.includes(current) && current !== '') {
      link.classList.add('active');
    }
  });
}

/* --- Animate stat counters --- */
function animateStats() {
  document.querySelectorAll('.stat-number').forEach(el => {
    const target = parseInt(el.dataset.target || el.textContent, 10);
    if (isNaN(target) || target < 2) return;
    let current = 0;
    const step  = Math.ceil(target / 30);
    el.textContent = '0';
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current >= target) clearInterval(timer);
    }, 25);
  });
}

/* --- Show toast notification --- */
function showToast(message, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
  }
  const id   = 'toast_' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
  bootstrap.Toast.getOrCreateInstance(document.getElementById(id), { delay: 3500 }).show();
}
