<?php
require_once "../../helpers/auth_check.php";
checkRole('evaluator');
require_once "../../config/db.php";

$pageTitle = "Final Evaluation";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<div class="main">
  <?php 
    $headerTitle = "Project Evaluation";
    $headerSubtitle = "Assess group performance and assign final marks";
    $showNotifications = false;
    $extraButtons = '<span id="sessionBadge" class="badge bg-secondary px-3 py-2 rounded-pill">Checking Session...</span>';
    require_once "../../includes/navbar.php";
  ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-lg">
        <div class="card-header">
          <span class="fw-bold text-white"><i class="bi bi-pencil-square me-2 text-primary"></i>Evaluation Form</span>
        </div>
        <div class="card-body p-4">
          <form id="evalForm" onsubmit="submitEval(event)">
            <div class="mb-3">
              <label class="form-label text-secondary small fw-bold">Select Group</label>
              <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="groupId" required>
                <option value="">Loading groups...</option>
              </select>
              <div class="form-text text-secondary" id="groupHint">Groups assigned to your current session.</div>
            </div>

            <div class="mb-3">
              <label class="form-label text-secondary small fw-bold">Marks (out of 20)</label>
              <div class="input-group">
                <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="marks" min="0" max="20" step="1" required/>
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">/ 20</span>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Feedback / Comments</label>
              <textarea class="form-control bg-dark border-secondary border-opacity-25 text-white" id="feedback" rows="5" required placeholder="Describe project strengths and weaknesses..."></textarea>
            </div>

            <button class="btn btn-primary w-100 py-2 fw-bold shadow-sm" type="submit" id="submitBtn">
              <i class="bi bi-check2-circle me-2"></i> Submit Evaluation
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white"><i class="bi bi-clock-history me-2 text-warning"></i>Recent Submissions</span>
          <button class="btn btn-link btn-sm text-secondary p-0 text-decoration-none" onclick="loadMyEvaluations()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
        <div class="card-body p-0">
          <div id="recentList" class="list-group list-group-flush bg-transparent">
            <div class="p-4 text-center text-secondary small">No evaluations submitted yet.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // requireAuth is already called by sidebar/app.js, but evaluators might need specific check
  checkSession();
  loadGroups();
  loadMyEvaluations();
});

async function checkSession() {
  try {
    const res = await fetch('../../api/evaluator/get_session_status.php');
    const data = await res.json();
    const badge = document.getElementById('sessionBadge');
    const form = document.getElementById('evalForm');
    
    if(data.success) {
      if(data.session.status === 'running') {
        badge.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill';
        badge.textContent = `Active Session: ${data.session.session} ${data.session.year}`;
      } else {
        badge.className = 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill';
        badge.textContent = 'Session Closed';
        form.querySelectorAll('input, select, textarea, button').forEach(el => el.disabled = true);
      }
    } else {
      badge.textContent = 'No active session';
      form.querySelectorAll('input, select, textarea, button').forEach(el => el.disabled = true);
    }
  } catch(e) { console.error(e); }
}

async function loadGroups() {
  try {
    const res = await fetch('../../api/evaluator/get_eval_groups.php');
    const data = await res.json();
    const select = document.getElementById('groupId');
    
    if(data.success) {
      if(data.groups.length === 0) {
        select.innerHTML = '<option value="">No groups found for your session</option>';
        return;
      }
      select.innerHTML = '<option value="">Select a group...</option>';
      data.groups.forEach(g => {
        select.innerHTML += `<option value="${g.id}">${g.name} - ${g.subject_title} (${g.members_str})</option>`;
      });
    }
  } catch(e) { console.error(e); }
}

async function submitEval(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const oldText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

  const payload = {
    group_id: document.getElementById('groupId').value,
    marks: document.getElementById('marks').value,
    feedback: document.getElementById('feedback').value
  };

  try {
    const res = await fetch('../../api/evaluator/submit_final_evaluation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success) {
      alert('Evaluation submitted successfully!');
      document.getElementById('evalForm').reset();
      loadMyEvaluations();
    } else {
      alert('Error: ' + data.message);
    }
  } catch(e) { 
    alert('Failed to connect to server');
  } finally {
    btn.disabled = false;
    btn.innerHTML = oldText;
  }
}

async function loadMyEvaluations() {
  try {
    const res = await fetch('../../api/evaluator/get_my_evaluations.php');
    const data = await res.json();
    const list = document.getElementById('recentList');
    
    if(data.success) {
      if(data.evaluations.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-secondary small">No evaluations submitted yet.</div>';
        return;
      }
      
      list.innerHTML = '';
      data.evaluations.forEach(e => {
        list.innerHTML += `
          <div class="list-group-item bg-transparent border-secondary border-opacity-10 p-4">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold text-white mb-1">${e.group_name}</div>
                <div class="text-secondary small">${e.subject_title}</div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-primary fs-5">${e.marks_out_of_20}<small class="text-secondary" style="font-size:0.7rem;">/20</small></div>
                <div class="text-secondary" style="font-size:0.65rem;">${new Date(e.created_at).toLocaleDateString()}</div>
              </div>
            </div>
            <div class="mt-2 text-secondary small text-truncate" style="max-width: 300px;">
              "${e.feedback}"
            </div>
          </div>
        `;
      });
    }
  } catch(e) { console.error(e); }
}
</script>

<?php require_once "../../includes/footer.php"; ?>
