<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

$dept_id = $_SESSION['user_dept_id'] ?? 0;
$dept_name_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM departments WHERE id = $dept_id"));
$dept_name = $dept_name_row ? $dept_name_row['name'] : 'All';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Review Proposals – PROVIA</title>
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
    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
    .proposal-card { transition: transform 0.2s; cursor: pointer; }
    .proposal-card:hover { transform: translateY(-3px); }
  </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
  <div class="p-4 border-bottom border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3">
      <img src="../../assets/img/LOGO.png" alt="University Logo" style="height: 40px; width: auto; object-fit: contain;">
      <div>
        <span class="fw-bold text-white fs-6 d-block">PROVIA</span>
        <small class="text-secondary" style="font-size: 0.7rem;">Manager Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link" href="users.php"><i class="bi bi-people-fill me-3"></i>Users</a></li>
      <li><a class="nav-link active" href="proposals.php"><i class="bi bi-file-earmark-text-fill me-3"></i>All Proposals</a></li>
      <li><a class="nav-link" href="supervisor-assignment.php"><i class="bi bi-person-badge-fill me-3"></i>Supervisor Assignment</a></li>
      <li><a class="nav-link" href="evaluations.php"><i class="bi bi-star-fill me-3"></i>Evaluations</a></li>
      <li><a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart-fill me-3"></i>Analytics</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-indigo-500 text-white d-flex align-items-center justify-content-center shadow-sm" style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">M</div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName">Manager</div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;" id="userRoll">PBL Manager</div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-0 fw-bold">Review Proposals (<?php echo htmlspecialchars($dept_name); ?>)</h4>
      <p class="text-muted small mb-0">Manage project submissions for your department</p>
    </div>
    <div class="btn-group">
      <button class="btn btn-outline-secondary btn-sm" onclick="loadProposals()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control border-start-0" id="propSearch" placeholder="Search proposals...">
      </div>
    </div>
    <div class="col-md-3">
      <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="classFilter">
        <option value="">All Classes</option>
        <!-- Populated by JS -->
      </select>
    </div>
  </div>

  <div id="proposalsGrid" class="row g-3">
    <div class="col-12 text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Loading proposals...</p>
    </div>
  </div>
</div>

<!-- Modal: Proposal Details -->
<div class="modal fade" id="proposalModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Proposal Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Filled by JS -->
      </div>
      <div class="modal-footer" id="modalFooter">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let allProposals = [];

async function loadProposals() {
  const grid = document.getElementById('proposalsGrid');
  try {
    const response = await fetch('../../api/manager/get_proposals.php');
    const result = await response.json();
    if (result.success) {
      allProposals = result.proposals;
      populateClassFilter(allProposals);
      renderProposals(allProposals);
    } else {
      grid.innerHTML = `<div class="col-12 text-center text-danger">Error: ${result.message}</div>`;
    }
  } catch (e) {
    grid.innerHTML = `<div class="col-12 text-center text-danger">Failed to load proposals</div>`;
  }
}

function renderProposals(props) {
  const grid = document.getElementById('proposalsGrid');
  if (props.length === 0) {
    grid.innerHTML = `<div class="col-12 text-center py-5 text-muted">No proposals found in your department.</div>`;
    return;
  }

  grid.innerHTML = props.map(p => {
    let statusClass = 'bg-warning';
    if (p.status === 'accepted') statusClass = 'bg-success';
    if (p.status === 'rejected') statusClass = 'bg-danger';

    return `
      <div class="col-md-6 col-lg-4">
        <div class="card proposal-card h-100 border-0 shadow-sm" onclick="viewProposal(${p.id})">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge ${statusClass} text-capitalize">${p.status}</span>
              <small class="text-muted">${new Date(p.submitted_at).toLocaleDateString()}</small>
            </div>
            <h6 class="fw-bold mb-1">${p.title}</h6>
            <p class="text-muted small mb-3 text-truncate-2">${p.description || 'No description provided.'}</p>
            <div class="d-flex align-items-center gap-2 mt-auto flex-wrap">
              <div class="px-2 py-1 rounded small border border-secondary border-opacity-25 bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-people me-1"></i> ${p.group_name}
              </div>
              <div class="px-2 py-1 rounded small border border-secondary border-opacity-25 bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-mortarboard me-1"></i> ${p.class_name}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function viewProposal(id) {
  const p = allProposals.find(item => item.id == id);
  if (!p) return;

  document.getElementById('modalTitle').textContent = p.title;
  document.getElementById('modalBody').innerHTML = `
    <div class="row g-3">
      <div class="col-md-6"><strong>Group:</strong> ${p.group_name}</div>
      <div class="col-md-6"><strong>Supervisor:</strong> ${p.supervisor_name || 'Unassigned'}</div>
      <div class="col-md-6"><strong>Program:</strong> ${p.program_name}</div>
      <div class="col-md-6"><strong>Department:</strong> ${p.department_name}</div>
      <div class="col-12"><hr><strong>Description:</strong><p class="mt-2 text-muted small">${p.description || 'N/A'}</p></div>
      <div class="col-12"><strong>Tools/Technologies:</strong><p class="mt-2 text-muted small">${p.tools || 'N/A'}</p></div>
      ${p.rejection_reason ? `
        <div class="col-12">
          <div class="p-3 bg-light bg-opacity-10 border rounded-3">
            <div class="fw-bold small mb-1">Supervisor Feedback:</div>
            <div class="small">"${p.rejection_reason}"</div>
          </div>
        </div>
      ` : ''}
    </div>
  `;



  new bootstrap.Modal(document.getElementById('proposalModal')).show();
}



function populateClassFilter(props) {
  const select = document.getElementById('classFilter');
  const classes = [...new Set(props.map(p => p.class_name))].sort();
  select.innerHTML = '<option value="">All Classes</option>' + 
    classes.map(c => `<option value="${c}">${c}</option>`).join('');
}

function filterProposals() {
  const q = document.getElementById('propSearch').value.toLowerCase();
  const status = document.getElementById('statusFilter').value;
  const cls = document.getElementById('classFilter').value;
  
  const filtered = allProposals.filter(p => {
    const matchesSearch = 
      p.title.toLowerCase().includes(q) || 
      p.group_name.toLowerCase().includes(q) || 
      (p.description || '').toLowerCase().includes(q) ||
      (p.leader_name || '').toLowerCase().includes(q);
      
    const matchesStatus = !status || p.status === status;
    const matchesClass = !cls || p.class_name === cls;
    
    return matchesSearch && matchesStatus && matchesClass;
  });
  renderProposals(filtered);
}

document.getElementById('propSearch').addEventListener('input', filterProposals);
document.getElementById('statusFilter').addEventListener('change', filterProposals);
document.getElementById('classFilter').addEventListener('change', filterProposals);

document.addEventListener('DOMContentLoaded', () => {
  requireAuth('pbl_manager');
  loadProposals();
});
</script>
</body>
</html>


