/* ===== auth.js — Login & Register ===== */

let currentRole = 'student';

/* --- Role tab selection --- */
function selectRole(role, btn) {
  currentRole = role;
  btn.closest('.btn-group').querySelectorAll('.role-btn').forEach(b => {
    b.classList.remove('active', 'btn-primary', 'text-white');
    b.classList.add('btn-outline-primary');
  });
  btn.classList.add('active', 'btn-primary', 'text-white');
  btn.classList.remove('btn-outline-primary');

  const idGroup = document.getElementById('regIdGroup');
  if (idGroup) idGroup.style.display = role === 'student' ? 'block' : 'none';
}

/* --- Handle Login --- */
function handleLogin(e) {
  e.preventDefault();
  const btn     = document.getElementById('loginBtn');
  const btnText = document.getElementById('loginBtnText');
  const spinner = document.getElementById('loginSpinner');

  btn.disabled = true;
  if (btnText) btnText.textContent = 'Signing in...';
  if (spinner) spinner.classList.remove('d-none');

  const email = document.getElementById('loginEmail').value.trim();
  const name  = email.split('@')[0].replace(/[^a-zA-Z]/g, ' ').trim();

  setTimeout(() => {
    sessionStorage.setItem('pbl_role',  currentRole);
    sessionStorage.setItem('pbl_name',  capitalize(name) || 'User');
    sessionStorage.setItem('pbl_email', email);

    const redirects = {
      student:    'pages/student/dashboard.html',
      supervisor: 'pages/supervisor/dashboard.html',
      manager:    'pages/manager/dashboard.html'
    };
    window.location.href = redirects[currentRole] || 'pages/student/dashboard.html';
  }, 1000);
}

/* --- Handle Register --- */
function handleRegister(e) {
  e.preventDefault();
  const first = document.getElementById('regFirst')?.value.trim() || '';
  const last  = document.getElementById('regLast')?.value.trim()  || '';
  const email = document.getElementById('regEmail')?.value.trim() || '';

  sessionStorage.setItem('pbl_role',  currentRole);
  sessionStorage.setItem('pbl_name',  `${first} ${last}`.trim());
  sessionStorage.setItem('pbl_email', email);

  const btn = e.submitter;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'; }

  setTimeout(() => {
    const redirects = {
      student:    '../../pages/student/dashboard.html',
      supervisor: '../../pages/supervisor/dashboard.html'
    };
    window.location.href = redirects[currentRole] || '../../pages/student/dashboard.html';
  }, 1000);
}

/* --- Guard: redirect to login if not authenticated --- */
function requireAuth() {
  const role = sessionStorage.getItem('pbl_role');
  if (!role) {
    window.location.href = getRootPath() + 'index.html';
  }
}

/* --- Get root path relative to current page depth --- */
function getRootPath() {
  const depth = window.location.pathname.split('/').filter(Boolean).length - 1;
  return '../'.repeat(Math.max(depth - 1, 0));
}

/* --- Capitalize helper --- */
function capitalize(s) {
  if (!s) return 'User';
  return s.charAt(0).toUpperCase() + s.slice(1);
}
