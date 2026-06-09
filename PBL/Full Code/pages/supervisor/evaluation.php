<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";
$pageTitle = "Evaluation – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$supervisor_id = $_SESSION['user_id'];
$is_mid_evaluator = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM mid_eval_sessions WHERE evaluator_id = $supervisor_id AND eval_date = CURDATE() AND CURTIME() BETWEEN start_time AND end_time AND status = 'active' LIMIT 1")) > 0;

// Removed redirect to allow showing the restriction message
?>




<div class="main">
  <div class="mb-5">
    <h3 class="mb-1 fw-bold text-white">Mid-Term Evaluation</h3>
    <p class="text-secondary mb-0">Record performance grades and provide qualitative feedback</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card shadow-lg">
        <div class="card-header">
          <span class="fw-bold text-white">Evaluation Form</span>
        </div>
        <div class="card-body p-4">
          <form id="evaluationForm" onsubmit="submitEvaluation(event)">
            <div class="row g-3 mb-4">
              <div class="col-md-12">
                <label class="form-label text-secondary small fw-bold">Select Project Group</label>
                <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="evalGroup" required>
                  <option value="">-- Choose Group --</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label text-secondary small fw-bold" id="scoreLabel">Obtained Marks</label>
                <div class="input-group">
                  <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="obtainedScore" min="0" max="5" step="0.5" placeholder="0.0" required/>
                  <span class="input-group-text bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25" id="maxScoreLabel">/5</span>
                </div>
                <small class="text-info" style="font-size: 0.7rem;">Marks for individual contribution.</small>
              </div>
              <div class="col-md-6">
                <label class="form-label text-secondary small fw-bold">Overall Project Progress (%)</label>
                <div class="input-group">
                  <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="progressPercent" min="0" max="100" step="1" value="0" required/>
                  <span class="input-group-text bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25">%</span>
                </div>
                <small class="text-info" style="font-size: 0.7rem;">Estimate of total project completion.</small>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Feedback Comments</label>
              <textarea class="form-control bg-dark border-secondary border-opacity-25 text-white" id="evalFeedback" rows="4" placeholder="Describe the student's progress and technical skills..." required></textarea>
            </div>

            <div class="mb-5">
              <label class="form-label text-secondary small fw-bold">Recommendations for Improvement</label>
              <textarea class="form-control bg-dark border-secondary border-opacity-25 text-white" id="evalRec" rows="2" placeholder="Specific steps for the student to take next..."></textarea>
            </div>

            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm" id="submitBtn">
                <i class="bi bi-check-circle-fill me-2"></i> Submit Evaluation
              </button>
              <button type="reset" class="btn btn-outline-secondary rounded-3 px-4">Clear Form</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-lg">
        <div class="card-header">
          <span class="fw-bold text-white">Recent Submissions</span>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush bg-transparent" id="evalList">
            <li class="list-group-item bg-transparent text-center py-5 text-secondary">
              <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-25"></i>
              No evaluations recorded yet.
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  requireAuth('supervisor');
  const isSessionActive = <?php echo $is_mid_evaluator ? 'true' : 'false'; ?>;
  
  if (!isSessionActive) {
    document.getElementById('evaluationForm').innerHTML = `
      <div class="alert alert-warning border-warning border-opacity-25 bg-warning bg-opacity-10 text-warning p-4 rounded-4">
        <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Access Restricted</h5>
        <p class="small mb-0">You cannot perform evaluations because the Manager has not yet activated a monitoring session for you at this time.</p>
      </div>
    `;
    // Still load recent evaluations so they can see past work
    loadRecentEvaluations();
  } else {
    loadGroups();
    loadRecentEvaluations();
  }
});

function loadGroups() {
  fetch('../../api/supervisor/get_groups_for_eval.php')
    .then(res => res.json())
    .then(res => {
      const sel = document.getElementById('evalGroup');
      if (res.success && res.groups.length > 0) {
        sel.innerHTML = '<option value="">-- Choose Group --</option>' + 
          res.groups.map(g => `<option value="${g.id}">${g.name} (${g.program_name})</option>`).join('');
        sel.disabled = false;
      } else {
        sel.innerHTML = '<option value="">No groups pending evaluation</option>';
        sel.disabled = true;
      }
    });
}

function loadRecentEvaluations() {
  fetch('../../api/supervisor/get_recent_evaluations.php')
    .then(res => res.json())
    .then(res => {
      const list = document.getElementById('evalList');
      if (res.success && res.evaluations.length > 0) {
        list.innerHTML = res.evaluations.map(e => {
          const score = e.tech_score;
          return `
            <li class="list-group-item bg-transparent border-secondary border-opacity-10 p-4">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <div class="fw-bold text-white small">${e.group_name}</div>
                  <div class="text-secondary" style="font-size:0.7rem;">${e.subject_title}</div>
                </div>
                <span class="badge bg-primary text-white px-3 rounded-pill shadow-sm">${score}/5</span>
              </div>
              <div class="text-secondary" style="font-size:0.65rem;">${new Date(e.created_at).toLocaleDateString()}</div>
            </li>
          `;
        }).join('');
      }
    });
}

async function submitEvaluation(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const oldHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

  const payload = {
    group_id: document.getElementById('evalGroup').value,
    tech_score: parseFloat(document.getElementById('obtainedScore').value),
    progress_percent: parseInt(document.getElementById('progressPercent').value),
    feedback: document.getElementById('evalFeedback').value,
    recommendations: document.getElementById('evalRec').value
  };

  try {
    const res = await fetch('../../api/supervisor/submit_evaluation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert('Evaluation submitted successfully!');
      e.target.reset();
      loadRecentEvaluations();
    } else {
      alert(data.message);
    }
  } catch (e) {
    alert('Failed to connect to server');
  } finally {
    btn.disabled = false;
    btn.innerHTML = oldHtml;
  }
}
</script>
<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

