<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";
$pageTitle = "Review Proposals – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$supervisor_id = $_SESSION['user_id'];
$is_mid_evaluator = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM mid_eval_sessions WHERE evaluator_id = $supervisor_id AND eval_date = CURDATE() AND CURTIME() BETWEEN start_time AND end_time AND status = 'active' LIMIT 1")) > 0;
?>

<style>
  .view-container { display: none; }
  .view-container.active { display: block; }
  
  .selection-hub { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
    gap: 24px; 
    margin-top: 40px;
  }
  
  .hub-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  .hub-card:hover {
    transform: translateY(-10px);
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
  }
  
  .hub-card i {
    font-size: 3.5rem;
    color: #6366f1;
    margin-bottom: 20px;
    display: block;
  }
  
  .hub-card h3 {
    color: #fff;
    font-weight: 700;
    margin-bottom: 12px;
  }
  
  .hub-card p {
    color: #94a3b8;
    font-size: 0.95rem;
    margin-bottom: 0;
  }

  .btn-back {
    display: inline-flex;
    align-items: center;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.9rem;
    margin-bottom: 24px;
    transition: color 0.2s;
  }
  
  .btn-back:hover { color: #6366f1; }
  .btn-back i { margin-right: 8px; }

  /* Table styling enhancements */
  .table-hover tbody tr:hover {
    background-color: rgba(99, 102, 241, 0.05);
  }
</style>




<div class="main">
  <!-- Notifications Top Bar -->
  <div class="d-flex justify-content-end mb-3">
    <div class="dropdown">
      <button class="btn btn-outline-secondary rounded-pill p-2 border-0 position-relative" type="button" id="notifLink" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell-fill fs-5 text-secondary"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge" style="font-size: 0.6rem; padding: 0.35em 0.65em;">0</span>
      </button>
      <div class="dropdown-menu dropdown-menu-dark dropdown-menu-end p-0 shadow-lg border-secondary mt-2" style="width: 320px; max-height: 400px; overflow-y: auto;" aria-labelledby="notifLink">
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-dark">
          <span class="fw-bold small">Recent Notifications</span>
          <button class="btn btn-link btn-sm p-0 text-secondary text-decoration-none small" onclick="markAllRead()">Clear</button>
        </div>
        <div id="notifList" class="small">
          <div class="p-4 text-center text-secondary">No new notifications</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Selection View -->
  <div id="selectionHub" class="view-container active">
    <div class="mb-5 text-center">
      <h2 class="fw-bold text-white">Submission Management</h2>
      <p class="text-secondary">Select a track to review student submissions</p>
    </div>
    
    <div class="selection-hub">
      <div class="hub-card" onclick="switchView('proposalView')">
        <i class="bi bi-file-earmark-check"></i>
        <h3>Review Proposals</h3>
        <p>Assess and approve initial project proposals for your assigned classes.</p>
      </div>

      <div class="hub-card" onclick="switchView('flexView')">
        <span class="badge bg-info text-white border border-white border-opacity-25 fw-bold px-3">END SEMESTER</span>
        <i class="bi bi-file-earmark-zip"></i>
        <h3>Flex Submissions</h3>
        <p>View final flex documents submitted by groups at the end of the semester.</p>
      </div>
    </div>
  </div>

  <!-- Proposal View -->
  <div id="proposalView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    
    <div class="mb-4">
      <h3 class="fw-bold text-white mb-1">Review Proposals</h3>
      <p class="text-secondary small">Assess and approve project submissions for your classes</p>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
      <div class="card-body py-2">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Search proposals..." id="searchInput" oninput="filterTable()"/>
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="filterSt" onchange="filterTable()">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card shadow-lg">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="proposalsTable">
            <thead class="bg-dark bg-opacity-50">
              <tr>
                <th class="ps-4">Title / Group</th>
                <th>Category</th>
                <th>Submitted</th>
                <th>Status</th>
                <th class="pe-4">Actions</th>
              </tr>
            </thead>
            <tbody id="proposalsTbody">
              <tr><td colspan="5" class="text-center py-5 text-secondary"><div class="spinner-border spinner-border-sm me-2"></div>Loading proposals...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Flex View -->
  <div id="flexView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    
    <div class="mb-4">
      <h3 class="fw-bold text-white mb-1">Flex Submissions</h3>
      <p class="text-secondary small">Final semester documents submitted by project groups</p>
    </div>

    <div class="card shadow-lg">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark bg-opacity-50">
              <tr>
                <th class="ps-4">Group Name</th>
                <th>Class / Program</th>
                <th>Submitted At</th>
                <th>Status</th>
                <th class="pe-4 text-end">Action</th>
              </tr>
            </thead>
            <tbody id="flexTbody">
              <tr><td colspan="4" class="text-center py-5 text-secondary">Loading flex submissions...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="reviewModalTitle">Review Proposal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="reviewModalBody" class="mb-4"></div>
        <div class="mb-3">
          <label class="form-label text-secondary small fw-bold text-uppercase">Feedback / Comments</label>
          <textarea class="form-control bg-dark border-secondary border-opacity-25 text-white" id="reviewComment" rows="4" placeholder="Provide constructive feedback..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-top border-secondary border-opacity-10" id="reviewModalFooter">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger rounded-pill px-4" onclick="submitReview('reject')"><i class="bi bi-x-lg me-1"></i> Reject</button>
        <button class="btn btn-success rounded-pill px-4" onclick="submitReview('approve')"><i class="bi bi-check-lg me-1"></i> Approve</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let proposals = [];
let activeProposalId = null;

function toViewModel(p) {
  let displayStatus = p.status.charAt(0).toUpperCase() + p.status.slice(1);
  if (displayStatus === 'Accepted') displayStatus = 'Approved';
  return {
    ...p,
    id: Number(p.id),
    displayStatus
  };
}

function renderTable(data) {
  const tbody = document.getElementById('proposalsTbody');
  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-secondary">No proposals found matching your criteria.</td></tr>';
    return;
  }
  tbody.innerHTML = data.map(p => `
    <tr>
      <td class="ps-4">
        <div class="fw-bold text-white">${p.title}</div>
        <div class="text-secondary small">Group: ${p.group_name}</div>
      </td>
      <td><span class="badge bg-primary text-white rounded-pill px-3 shadow-sm" style="font-size: 0.7rem;">${p.category || 'PBL'}</span></td>
      <td><small class="text-secondary">${new Date(p.submitted_at).toLocaleDateString()}</small></td>
      <td>
        <span class="badge bg-${p.displayStatus==='Pending'?'warning':(p.displayStatus==='Approved'?'success':'danger')} bg-opacity-10 text-${p.displayStatus==='Pending'?'warning':(p.displayStatus==='Approved'?'success':'danger')} border border-${p.displayStatus==='Pending'?'warning':(p.displayStatus==='Approved'?'success':'danger')} border-opacity-25 px-3 rounded-pill">${p.displayStatus}</span>
      </td>
      <td class="pe-4">
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openReview(${p.id})">Review</button>
      </td>
    </tr>
  `).join('');
}

async function loadProposals() {
  const tbody = document.getElementById('proposalsTbody');
  try {
    const res = await fetch('../../api/supervisor/get_proposals.php');
    const data = await res.json();
    if (data.success) {
      proposals = data.proposals.map(toViewModel);
      renderTable(proposals);
    } else {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger"><i class="bi bi-exclamation-circle me-2"></i>Error: ${data.message}</td></tr>`;
    }
  } catch (e) { 
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger"><i class="bi bi-wifi-off me-2"></i>Connection error. Please try again later.</td></tr>`;
  }
}

function openReview(id) {
  activeProposalId = id;
  const p = proposals.find(x => x.id === id);
  document.getElementById('reviewModalTitle').textContent = p.title;
  document.getElementById('reviewModalBody').innerHTML = `
    <div class="row g-3 mb-4">
      <div class="col-md-6"><span class="text-secondary small fw-bold text-uppercase d-block mb-1">Group Name</span><div class="text-white">${p.group_name}</div></div>
      <div class="col-md-6"><span class="text-secondary small fw-bold text-uppercase d-block mb-1">Program</span><div class="text-white">${p.program_name || 'N/A'}</div></div>
      <div class="col-12"><span class="text-secondary small fw-bold text-uppercase d-block mb-1">Description</span><div class="text-secondary small">${p.description}</div></div>
    </div>
    <div class="row g-3 mb-4">
      <div class="col-md-12">
        <span class="text-secondary small fw-bold text-uppercase d-block mb-2">Attachments</span>
        ${(p.attachments && p.attachments.length) ? p.attachments.map(a => `
          <a href="../../${a.file_path}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 me-2 mb-2">
            <i class="bi bi-file-earmark-pdf me-1"></i> ${a.file_name}
          </a>
        `).join('') : '<div class="text-muted small">No attachments uploaded.</div>'}
      </div>
    </div>
    <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-10">
      <div class="text-secondary small fw-bold text-uppercase mb-2">Team Members</div>
      ${p.members.map(m => `<div class="small text-white py-1"><i class="bi bi-person me-2"></i>${m.name} (${m.roll_number})</div>`).join('')}
    </div>
  `;
  document.getElementById('reviewComment').value = p.rejection_reason || '';
  document.getElementById('reviewComment').disabled = (p.status === 'accepted');

  const footer = document.getElementById('reviewModalFooter');
  if (p.status === 'accepted') {
    footer.innerHTML = `
      <div class="d-flex align-items-center justify-content-between w-100">
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill small">
          <i class="bi bi-check-circle-fill me-2"></i>Permanently Approved
        </span>
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    `;
  } else {
    footer.innerHTML = `
      <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-danger rounded-pill px-4" onclick="submitReview('reject')"><i class="bi bi-x-lg me-1"></i> Reject</button>
      <button class="btn btn-success rounded-pill px-4" onclick="submitReview('approve')"><i class="bi bi-check-lg me-1"></i> Approve</button>
    `;
  }

  new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

async function submitReview(action) {
  const status = action === 'approve' ? 'Approved' : 'Rejected';
  const comment = document.getElementById('reviewComment').value;
  try {
    const res = await fetch('../../api/supervisor/update_proposal_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ proposal_id: activeProposalId, status, comment })
    });
    const result = await res.json();
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
      loadProposals();
    }
  } catch (e) { alert("Failed to submit review"); }
}

function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const st = document.getElementById('filterSt').value;
  const filtered = proposals.filter(p =>
    (!q || p.title.toLowerCase().includes(q) || p.group_name.toLowerCase().includes(q)) &&
    (!st || p.displayStatus === st)
  );
  renderTable(filtered);
}

function switchView(viewId) {
  document.querySelectorAll('.view-container').forEach(v => v.classList.remove('active'));
  document.getElementById(viewId).classList.add('active');
  if (viewId === 'proposalView') loadProposals();
  if (viewId === 'flexView') loadFlexSubmissions();
}

async function loadFlexSubmissions() {
  const tbody = document.getElementById('flexTbody');
  try {
    const res = await fetch('../../api/supervisor/get_flex_submissions.php');
    const data = await res.json();
    if (data.success) {
      renderFlexTable(data.submissions);
    } else {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Error: ${data.message}</td></tr>`;
    }
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Connection error.</td></tr>`;
  }
}

function renderFlexTable(data) {
  const tbody = document.getElementById('flexTbody');
  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-secondary">No flex submissions received yet.</td></tr>';
    return;
  }
  tbody.innerHTML = data.map(f => `
    <tr>
      <td class="ps-4">
        <div class="fw-bold text-white">${f.group_name}</div>
      </td>
      <td>
        <div class="small text-secondary">${f.program_name}</div>
        <div class="smaller text-muted" style="font-size:0.7rem;">${f.session} (Sem ${f.semester_number})</div>
      </td>
      <td><small class="text-secondary">${new Date(f.created_at).toLocaleString()}</small></td>
      <td>
        <span class="badge bg-${f.status === 'pending' ? 'warning' : (f.status === 'accepted' ? 'success' : 'danger')} bg-opacity-10 text-${f.status === 'pending' ? 'warning' : (f.status === 'accepted' ? 'success' : 'danger')} border border-${f.status === 'pending' ? 'warning' : (f.status === 'accepted' ? 'success' : 'danger')} border-opacity-25 px-3 rounded-pill">
          ${f.status.charAt(0).toUpperCase() + f.status.slice(1)}
        </span>
      </td>
      <td class="pe-4 text-end">
        <div class="btn-group">
          <a href="../../${f.file_path}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 me-2">
            <i class="bi bi-eye me-1"></i> View
          </a>
          ${f.status === 'pending' ? `
            <button onclick="updateFlexStatus(${f.id}, 'accepted')" class="btn btn-sm btn-success rounded-pill px-3 me-2">
              <i class="bi bi-check2"></i> Approve
            </button>
            <button onclick="updateFlexStatus(${f.id}, 'rejected')" class="btn btn-sm btn-danger rounded-pill px-3">
              <i class="bi bi-x-lg"></i> Reject
            </button>
          ` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

async function updateFlexStatus(id, status) {
  let feedback = "";
  if (status === 'rejected') {
    feedback = prompt("Please provide a reason for rejection:");
    if (feedback === null) return; // User cancelled
    if (feedback.trim() === "") {
      alert("A rejection reason is required.");
      return;
    }
  } else {
    if (!confirm(`Are you sure you want to approve this flex submission?`)) return;
  }
  
  try {
    const res = await fetch('../../api/supervisor/update_flex_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ flex_id: id, status: status, feedback: feedback })
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      loadFlexSubmissions();
    } else {
      alert('Error: ' + data.message);
    }
  } catch (e) {
    alert('Connection error.');
  }
}

async function loadNotifications() {
  try {
    const res = await fetch('../../api/get_notifications.php');
    const data = await res.json();
    if (data.success) {
      const list = document.getElementById('notifList');
      const badge = document.getElementById('notifBadge');
      const unread = data.notifications.filter(n => !n.is_read);
      
      if (unread.length > 0) {
        badge.textContent = unread.length;
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }

      if (data.notifications.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-secondary">No new notifications</div>';
        return;
      }

      list.innerHTML = data.notifications.map(n => `
        <div class="p-3 border-bottom border-secondary border-opacity-25 ${n.is_read ? 'bg-transparent' : 'bg-white bg-opacity-5'}" onclick="handleNotifClick(${n.id}, '${n.link}')" style="cursor:pointer;">
          <div class="mb-1 text-white">${n.message}</div>
          <div class="text-secondary smaller" style="font-size:0.7rem;">${new Date(n.created_at).toLocaleString()}</div>
        </div>
      `).join('');
    }
  } catch (e) {}
}

async function handleNotifClick(id, link) {
  await fetch(`../../api/get_notifications.php?mark_read=${id}`);
  if (link) window.location.href = link;
  else loadNotifications();
}

async function markAllRead() {
  const badge = document.getElementById('notifBadge');
  badge.classList.add('d-none');
  loadNotifications();
}

document.addEventListener('DOMContentLoaded', () => {
  loadNotifications();
});
</script>
<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

