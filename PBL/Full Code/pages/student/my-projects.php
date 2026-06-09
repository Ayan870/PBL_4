<?php
require_once "../../helpers/auth_check.php";
checkRole('student');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Projects – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body{background:#0f172a;}
    .sidebar{min-height:100vh;background:#1e293b;border-right:1px solid #334155;width:240px;position:fixed;top:0;left:0;}
    .sidebar .nav-link{color:#94a3b8;font-size:0.9rem;padding:12px 16px;border-radius:8px;margin:4px 12px;transition:all 0.2s;}
    .sidebar .nav-link:hover{background:rgba(79, 70, 229, 0.1);color:#818cf8;}
    .sidebar .nav-link.active{background:#4f46e5;color:#fff;box-shadow:0 4px 12px rgba(79, 70, 229, 0.3);}
    .main{margin-left:240px;padding:32px;min-height:100vh;}
    .card{background:#1e293b;border:1px solid #334155;border-radius:16px;overflow:hidden;}
    .card-header{background:rgba(255,255,255,0.03);border-bottom:1px solid #334155;padding:20px 24px;}
    .project-card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:24px;transition:all 0.2s;height:100%;}
    .project-card:hover{transform:translateY(-5px);border-color:#4f46e5;box-shadow:0 12px 24px rgba(0,0,0,0.2);}
    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
  </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
  <div class="p-4 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3">
      <img src="../../assets/img/LOGO.png" alt="University Logo" style="height: 40px; width: auto; object-fit: contain;">
      <div>
        <span class="fw-bold text-white fs-6 d-block">PROVIA</span>
        <small class="text-secondary" style="font-size: 0.7rem;">Student Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link" href="submit-proposal.php"><i class="bi bi-file-earmark-plus-fill me-3"></i>Submit Proposal</a></li>
      <li><a class="nav-link active" href="my-projects.php"><i class="bi bi-folder-fill me-3"></i>My Projects</a></li>
      <li><a class="nav-link" href="feedback.php"><i class="bi bi-hand-thumbs-up-fill me-3"></i>Feedback</a></li>
      <li><a class="nav-link" href="results.php"><i class="bi bi-bar-chart-fill me-3"></i>Results</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-indigo-500 text-white d-flex align-items-center justify-content-center shadow-sm" style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">S</div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName">Student</div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;" id="userRoll">Roll No</div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">My Projects</h3>
      <p class="text-secondary mb-0">Manage your group and track project proposals</p>
    </div>
    <a href="submit-proposal.php" class="btn btn-primary rounded-3 px-4 shadow-sm"><i class="bi bi-plus-lg me-2"></i> New Proposal</a>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-lg-6">
      <div class="card shadow-lg h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white">My Group</span>
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 rounded-pill">Team Info</span>
        </div>
        <div class="card-body" id="myGroupBox">
          <div class="text-center py-4">
            <div class="spinner-border text-primary spinner-border-sm mb-2"></div>
            <div class="text-secondary small">Loading group details...</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-lg h-100">
        <div class="card-header">
          <span class="fw-bold text-white">Group Management</span>
        </div>
        <div class="card-body">
          <form id="inviteForm" class="mb-4">
            <label class="form-label text-secondary small fw-bold">Invite Classmate (Roll No.)</label>
            <div class="input-group">
              <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="inviteRollInput" placeholder="SU-BSCSM-F24-0XX" required/>
              <button class="btn btn-primary px-4" type="submit" id="sendInviteBtn">Invite</button>
            </div>
          </form>
          <div class="fw-bold text-white small mb-3">Incoming Invitations</div>
          <div id="incomingInvites">
            <div class="text-secondary small">Checking for requests...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body py-2 px-3">
      <div class="row g-3 align-items-center">
        <div class="col-md-6">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-transparent border-0 text-secondary"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-transparent border-0 text-white" placeholder="Filter projects..." id="searchProj" oninput="filterProjects()">
          </div>
        </div>
        <div class="col-md-6 text-md-end">
          <select class="form-select form-select-sm d-inline-block w-auto bg-dark border-secondary border-opacity-25 text-white" id="filterStatus" onchange="filterProjects()">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4" id="projectsGrid">
    <div class="col-12 text-center py-5 text-secondary">
      <div class="spinner-border text-primary mb-3"></div>
      <p>Fetching your project list...</p>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalTitle">Project Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="modalBody"></div>
      <div class="modal-footer border-secondary border-opacity-10">
        <a href="javascript:void(0)" id="modalChatBtn" class="btn btn-primary rounded-pill px-4 btn-sm">Message Supervisor</a>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js?v=1.1"></script>
<script src="../../assets/js/groups.js?v=1.3"></script>
<script>
let projects = [];

async function loadProposals() {
  const grid = document.getElementById('projectsGrid');
  try {
    const res = await fetch('../../api/student/get_proposals.php');
    const data = await res.json();
    if (data.success) {
      projects = data.proposals;
      filterProjects();
    } else {
      grid.innerHTML = `<div class="col-12 text-center py-5 text-danger"><i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>Error: ${data.message}</div>`;
    }
  } catch (e) { 
    grid.innerHTML = `<div class="col-12 text-center py-5 text-danger"><i class="bi bi-wifi-off fs-1 d-block mb-3"></i>Connection failed.</div>`;
  }
}

function renderProjects(list) {
  const grid = document.getElementById('projectsGrid');
  if (!list.length) {
    grid.innerHTML = `<div class="col-12 text-center py-5 text-secondary"><i class="bi bi-folder-x fs-1 d-block mb-2 opacity-25"></i>No project proposals found.</div>`;
    return;
  }
  grid.innerHTML = list.map(p => {
    const statusLabel = p.status.charAt(0).toUpperCase() + p.status.slice(1);
    let badgeClass = 'bg-secondary';
    if (p.status === 'pending') badgeClass = 'bg-warning text-dark';
    if (p.status === 'approved') badgeClass = 'bg-success';
    if (p.status === 'rejected') badgeClass = 'bg-danger';

    return `
    <div class="col-md-6 col-lg-4">
      <div class="project-card d-flex flex-column shadow-sm">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <span class="badge ${badgeClass} bg-opacity-10 text-${p.status === 'pending' ? 'warning' : (p.status === 'approved' ? 'success' : 'danger')} border border-${p.status === 'pending' ? 'warning' : (p.status === 'approved' ? 'success' : 'danger')} border-opacity-25 px-3 rounded-pill" style="font-size:0.7rem;">${statusLabel}</span>
          <span class="text-secondary" style="font-size:0.7rem;">${p.program_name}</span>
        </div>
        <h6 class="text-white fw-bold mb-2 text-truncate" title="${p.title}">${p.title}</h6>
        <p class="text-secondary small mb-4 flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">${p.description}</p>
        <div class="mb-4 small">
          <div class="text-secondary mb-1"><i class="bi bi-person-badge me-2"></i>Sup: <span class="text-white">${p.supervisor_name}</span></div>
          <div class="text-secondary"><i class="bi bi-tag me-2"></i>${p.category}</div>
        </div>
        <button class="btn btn-sm btn-outline-primary rounded-pill w-100" onclick="viewProject(${p.id})">View Details</button>
      </div>
    </div>`;
  }).join('');
}

function viewProject(id) {
  const p = projects.find(x => x.id === id);
  if (!p) return;
  
  let statusBadge = p.status === 'approved' ? 'success' : (p.status === 'pending' ? 'warning text-dark' : 'danger');

  document.getElementById('modalTitle').textContent = p.title;
  document.getElementById('modalBody').innerHTML = `
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <label class="text-secondary small fw-bold d-block mb-1">Project Category</label>
        <div class="text-white">${p.category}</div>
      </div>
      <div class="col-md-6">
        <label class="text-secondary small fw-bold d-block mb-1">Status</label>
        <span class="badge bg-${statusBadge} bg-opacity-10 text-${p.status === 'pending' ? 'warning' : statusBadge} border border-${p.status === 'pending' ? 'warning' : statusBadge} border-opacity-25 px-3 rounded-pill">${p.status.toUpperCase()}</span>
      </div>
      <div class="col-md-6">
        <label class="text-secondary small fw-bold d-block mb-1">Supervisor</label>
        <div class="text-white">${p.supervisor_name}</div>
      </div>
      <div class="col-md-6">
        <label class="text-secondary small fw-bold d-block mb-1">Program/Class</label>
        <div class="text-white">${p.program_name}</div>
      </div>
    </div>
    <div class="mb-4">
      <label class="text-secondary small fw-bold d-block mb-2">Detailed Abstract</label>
      <div class="text-secondary bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-10 small" style="line-height:1.6;">${p.description}</div>
    </div>
    ${p.rejection_reason ? `
      <div class="p-3 ${p.status === 'rejected' ? 'bg-danger' : 'bg-success'} bg-opacity-10 border ${p.status === 'rejected' ? 'border-danger' : 'border-success'} border-opacity-25 rounded-3 mb-4">
        <div class="${p.status === 'rejected' ? 'text-danger' : 'text-success'} small fw-bold mb-1">Supervisor Feedback:</div>
        <div class="text-white small">"${p.rejection_reason}"</div>
      </div>
    ` : ''}
  `;
  
  // Set supervisor ID on the chat button
  const chatBtn = document.getElementById('modalChatBtn');
  if (p.supervisor_id) {
    chatBtn.onclick = () => {
      if (window.pblChat) window.pblChat.selectContactById(p.supervisor_id);
      bootstrap.Modal.getInstance(document.getElementById('projectModal')).hide();
    };
  } else {
    chatBtn.classList.add('d-none');
  }

  new bootstrap.Modal(document.getElementById('projectModal')).show();
}

function filterProjects() {
  const q   = document.getElementById('searchProj').value.toLowerCase();
  const st  = document.getElementById('filterStatus').value.toLowerCase();
  const filtered = projects.filter(p =>
    (!q   || p.title.toLowerCase().includes(q)) &&
    (!st  || p.status === st)
  );
  renderProjects(filtered);
}

document.addEventListener('DOMContentLoaded', () => {
  requireAuth('student');
  loadProposals();
});
</script>
<?php include_once "../shared/chat_init.php"; ?>
</body>
</html>


