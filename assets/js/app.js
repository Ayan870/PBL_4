/* ===== app.js — Dashboard Common Logic ===== */

document.addEventListener('DOMContentLoaded', function () {
  requireAuth();
  loadUserInfo();
  injectChangePasswordLink();
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
    const labels = { student: 'Student', supervisor: 'Supervisor', manager: 'PBL Manager', evaluator: 'Evaluator' };
    roleLabel.textContent = labels[role] || role;
  }
}

/* --- Logout --- */
function logout() {
  sessionStorage.clear();
  window.location.href = pblUrlFromRoot('index.html');
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

/* --- URL helpers (works for file:// and http://) --- */
function pblProjectBaseUrl() {
  const url = new URL(window.location.href);
  const path = url.pathname || '/';
  const marker = '/pages/';
  const idx = path.toLowerCase().lastIndexOf(marker);
  const basePath = idx >= 0 ? path.slice(0, idx) : path.replace(/\/[^\/]*$/, '');
  url.pathname = (basePath.endsWith('/') ? basePath : basePath + '/') ;
  url.search = '';
  url.hash = '';
  return url.toString();
}

function pblUrlFromRoot(relativePath) {
  const rel = String(relativePath || '').replace(/^\/+/, '');
  return new URL(rel, pblProjectBaseUrl()).toString();
}

/* --- Guard: redirect to login if not authenticated --- */
function requireAuth() {
  const role = sessionStorage.getItem('pbl_role');
  if (!role) {
    window.location.href = pblUrlFromRoot('index.html');
    return;
  }
  if (role === 'student' && !sessionStorage.getItem('pbl_roll_number')) {
    sessionStorage.clear();
    window.location.href = pblUrlFromRoot('index.html');
  }
}

/* --- Add "Change Password" link (if logged in) --- */
function injectChangePasswordLink() {
  const logoutBtn = document.querySelector('button[onclick="logout()"]');
  if (!logoutBtn) return;
  if (document.getElementById('changePwBtn')) return;

  const a = document.createElement('a');
  a.id = 'changePwBtn';
  a.className = 'btn btn-outline-primary btn-sm w-100 mb-2';
  a.href = pblUrlFromRoot('pages/shared/change-password.html');
  a.innerHTML = '<i class="bi bi-key me-1"></i>Change Password';

  logoutBtn.parentElement?.insertBefore(a, logoutBtn);
}
