document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('changePwForm');
  const whoLabel = document.getElementById('whoLabel');
  const backLink = document.getElementById('backLink');

  function showAlert(message, type) {
    const existing = document.getElementById('pwAlert');
    if (existing) existing.remove();
    const el = document.createElement('div');
    el.id = 'pwAlert';
    el.className = 'alert alert-' + (type || 'danger') + ' mt-3 py-2 small';
    el.textContent = message;
    form?.insertAdjacentElement('afterend', el);
  }

  function roleHome(role) {
    if (role === 'student') return 'pages/student/dashboard.php';
    if (role === 'supervisor') return 'pages/supervisor/dashboard.php';
    if (role === 'pbl_manager' || role === 'manager') return 'pages/manager/dashboard.php';
    if (role === 'evaluator') return 'pages/evaluator/final-evaluation.php';
    return 'index.php';
  }

  function applySession(session) {
    if (!session) return;
    if (whoLabel) whoLabel.textContent = session.name ? (session.name + ' (' + (session.role || '') + ')') : '';
    if (backLink) backLink.href = pblUrlFromRoot(roleHome(session.role));
  }

  // app.js populates window.__pblSession; fallback to fetch if needed
  if (window.__pblSession) {
    applySession(window.__pblSession);
  } else {
    fetch(pblUrlFromRoot('api/auth/check_session.php'))
      .then(function (r) { return r.json(); })
      .then(function (s) { if (s && s.logged_in) applySession(s); })
      .catch(function () {});
  }

  form?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const currentPw = (document.getElementById('currentPw')?.value || '').trim();
    const newPw = (document.getElementById('newPw')?.value || '').trim();
    const confirmPw = (document.getElementById('confirmPw')?.value || '').trim();

    if (!currentPw || !newPw) return showAlert('Please fill in all fields.', 'danger');
    if (newPw.length < 6) return showAlert('New password must be at least 6 characters.', 'danger');
    if (newPw !== confirmPw) return showAlert('New password and confirmation do not match.', 'danger');

    try {
      const res = await fetch(pblUrlFromRoot('api/auth/change_password.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current_password: currentPw, new_password: newPw }),
      });
      const out = await res.json();
      if (!out || !out.success) return showAlert(out?.message || 'Failed to change password.', 'danger');
      showAlert(out.message || 'Password updated.', 'success');
      setTimeout(function () {
        window.location.href = backLink?.href || pblUrlFromRoot('index.php');
      }, 900);
    } catch (err) {
      showAlert('Cannot connect to server.', 'danger');
    }
  });
});

