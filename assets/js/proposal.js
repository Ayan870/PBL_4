/* ===== proposal.js — Multi-Step Proposal Form (Bootstrap 5) ===== */

let currentStep  = 1;
const totalSteps = 4;
let memberCount  = 1;

document.addEventListener('DOMContentLoaded', function () {
  showStep(1);
  initWordCounter();
  initFileDrop();
});

/* --- Show step --- */
function showStep(step) {
  for (let i = 1; i <= totalSteps; i++) {
    const panel = document.getElementById(`fstep-${i}`);
    const circle = document.getElementById(`step-ind-${i}`);
    if (panel) panel.classList.remove('active');
    if (circle) {
      circle.classList.remove('active', 'done');
      // Reset label color next to circle
      const label = circle.nextElementSibling;
      if (label && label.tagName === 'SPAN') label.classList.replace('text-primary', 'text-muted');
    }
  }

  const panel  = document.getElementById(`fstep-${step}`);
  const circle = document.getElementById(`step-ind-${step}`);
  if (panel)  panel.classList.add('active');
  if (circle) {
    circle.classList.add('active');
    const label = circle.nextElementSibling;
    if (label && label.tagName === 'SPAN') {
      label.classList.replace('text-muted', 'text-primary');
    }
  }

  // Mark done steps
  for (let i = 1; i < step; i++) {
    const c = document.getElementById(`step-ind-${i}`);
    if (c) c.classList.add('done');
    const line = document.getElementById(`line-${i}`);
    if (line) line.classList.add('done');
  }
  // Reset forward lines
  for (let i = step; i <= totalSteps; i++) {
    const line = document.getElementById(`line-${i}`);
    if (line) line.classList.remove('done');
  }

  currentStep = step;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* --- Next --- */
function nextStep(from) {
  if (!validateStep(from)) return;
  if (from === 3) buildReviewPanel();
  if (from < totalSteps) showStep(from + 1);
}

/* --- Prev --- */
function prevStep(from) {
  if (from > 1) showStep(from - 1);
}

/* --- Validate --- */
function validateStep(step) {
  let valid = true;
  let firstError = null;

  const check = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (!el.value.trim()) {
      el.classList.add('is-invalid');
      if (!firstError) firstError = el;
      valid = false;
    } else {
      el.classList.remove('is-invalid');
    }
    el.addEventListener('input', () => el.classList.remove('is-invalid'), { once: true });
    el.addEventListener('change', () => el.classList.remove('is-invalid'), { once: true });
  };

  if (step === 1) {
    ['projTitle', 'projDept', 'projCategory', 'projSupervisor', 'projSemester'].forEach(check);
  }
  if (step === 2) {
    ['projAbstract', 'projObjectives', 'projMethodology'].forEach(check);
  }

  if (!valid && firstError) firstError.focus();
  if (!valid) showAlert('Please fill in all required fields.', 'danger');
  return valid;
}

/* --- Word counter --- */
function initWordCounter() {
  const ta  = document.getElementById('projAbstract');
  const cnt = document.getElementById('abstractCount');
  if (!ta || !cnt) return;
  ta.addEventListener('input', function () {
    const words = this.value.trim().split(/\s+/).filter(w => w.length > 0).length;
    cnt.textContent = words;
    cnt.style.color = words > 400 ? 'red' : '';
  });
}

/* --- Add team member --- */
function addTeamMember() {
  if (memberCount >= 4) {
    showAlert('Maximum 4 team members allowed.', 'warning');
    return;
  }
  memberCount++;
  const list = document.getElementById('teamList');
  const row  = document.createElement('div');
  row.className = 'row g-2 mb-2 team-row';
  row.innerHTML = `
    <div class="col-5"><input type="text" class="form-control form-control-sm member-name" placeholder="Member name"/></div>
    <div class="col-5"><input type="text" class="form-control form-control-sm member-id" placeholder="Student ID"/></div>
    <div class="col-2">
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.team-row').remove(); memberCount--;" title="Remove">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  `;
  list.appendChild(row);
}

/* --- File drop zone --- */
function initFileDrop() {
  const zone = document.getElementById('fileDrop');
  if (!zone) return;
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) displayFile(file);
  });
}

function handleFile(input) {
  if (input.files && input.files[0]) displayFile(input.files[0]);
}

function displayFile(file) {
  const allowed = ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
  if (!allowed.includes(file.type)) { showAlert('Only PDF, DOC, DOCX files allowed.', 'danger'); return; }
  if (file.size > 10 * 1024 * 1024) { showAlert('File must be under 10MB.', 'danger'); return; }

  document.getElementById('fileDropInner').classList.add('d-none');
  const preview = document.getElementById('filePreview');
  preview.classList.remove('d-none');
  document.getElementById('fileName').textContent = file.name;
  document.getElementById('fileSize').textContent  = formatBytes(file.size);
  window._selectedFile = file.name;
}

function clearFile(e) {
  e.stopPropagation();
  document.getElementById('fileDropInner').classList.remove('d-none');
  document.getElementById('filePreview').classList.add('d-none');
  document.getElementById('fileInput').value = '';
  window._selectedFile = null;
}

/* --- Build review table --- */
function buildReviewPanel() {
  const tbody = document.getElementById('reviewTableBody');
  if (!tbody) return;

  const rows = [
    ['Project Title',    'projTitle'],
    ['Department',       'projDept'],
    ['Category',         'projCategory'],
    ['Supervisor',       'projSupervisor'],
    ['Semester',         'projSemester'],
    ['Abstract',         'projAbstract'],
    ['Objectives',       'projObjectives'],
    ['Methodology',      'projMethodology'],
    ['Tools & Tech',     'projTools'],
  ];

  let html = '';
  rows.forEach(([label, id]) => {
    const el  = document.getElementById(id);
    if (!el) return;
    let val = el.value.trim() || '—';
    if (val.length > 120) val = val.substring(0, 120) + '…';
    html += `<tr><th class="text-muted fw-normal" style="width:140px;">${label}</th><td>${htmlEscape(val)}</td></tr>`;
  });

  // Team members
  const members = [...document.querySelectorAll('.member-name')].map(el => el.value.trim()).filter(Boolean);
  if (members.length) {
    html += `<tr><th class="text-muted fw-normal">Team Members</th><td>${members.map(htmlEscape).join(', ')}</td></tr>`;
  }

  // File
  if (window._selectedFile) {
    html += `<tr><th class="text-muted fw-normal">Document</th><td>📄 ${htmlEscape(window._selectedFile)}</td></tr>`;
  }

  tbody.innerHTML = html;
}

/* --- Submit --- */
function submitProposal() {
  const check = document.getElementById('declareCheck');
  if (!check || !check.checked) {
    showAlert('Please tick the declaration checkbox before submitting.', 'warning');
    return;
  }

  const btn = document.getElementById('submitBtn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting…';
  }

  setTimeout(() => {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send me-1"></i> Submit Proposal'; }
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
  }, 1500);
}

/* --- Show inline alert --- */
function showAlert(message, type) {
  let alertBox = document.getElementById('proposalAlert');
  if (!alertBox) {
    alertBox = document.createElement('div');
    alertBox.id = 'proposalAlert';
    const card = document.querySelector('.card-body');
    if (card) card.insertBefore(alertBox, card.firstChild);
  }
  alertBox.className = `alert alert-${type} alert-dismissible fade show py-2 small`;
  alertBox.innerHTML = `${message} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
  setTimeout(() => { if (alertBox) alertBox.remove(); }, 4000);
}

/* --- Helpers --- */
function htmlEscape(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}
