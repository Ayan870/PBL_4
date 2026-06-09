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
  <title>Users – PROVIA</title>
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
    .stat-card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:24px;transition:transform 0.2s;height:100%;}
    .stat-card:hover{transform:translateY(-5px);}
    .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:16px;}
    .card{background:#1e293b;border:1px solid #334155;border-radius:16px;overflow:hidden;}
    .card-header{background:rgba(255,255,255,0.03);border-bottom:1px solid #334155;padding:20px 24px;}
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
        <small class="text-secondary" style="font-size: 0.7rem;">Manager Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link active" href="users.php"><i class="bi bi-people-fill me-3"></i>Users</a></li>
      <li><a class="nav-link" href="proposals.php"><i class="bi bi-file-earmark-text-fill me-3"></i>All Proposals</a></li>
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
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">User Management</h3>
      <p class="text-secondary mb-0"><?php echo htmlspecialchars($dept_name); ?> Department Faculty & Students</p>
    </div>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Users</div>
        <div class="fw-bold fs-3 text-white" id="statTotalUsers">0</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-mortarboard-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Students</div>
        <div class="fw-bold fs-3 text-white" id="statStudents">0</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-badge-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Supervisors</div>
        <div class="fw-bold fs-3 text-white" id="statSupervisors">0</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-check-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Active Now</div>
        <div class="fw-bold fs-3 text-white" id="statActive">0</div>
      </div>
    </div>
  </div>

  <div class="card shadow-lg">
    <div class="card-header py-3">
      <div class="row g-3 align-items-center">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Search by name, email, roll..." id="userSearch">
          </div>
        </div>
        <div class="col-md-3">
          <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="roleFilter">
            <option value="">All Roles</option>
            <option value="student">Students</option>
            <option value="supervisor">Supervisors</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">User Details</th>
              <th>ID / Roll</th>
              <th>Role</th>
              <th>Program</th>
              <th class="pe-4">Proposal Status</th>
            </tr>
          </thead>
          <tbody id="userTableBody">
            <tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary spinner-border-sm me-2"></div>Loading users...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let allUsers = [];

async function loadUsersFromDb() {
  const tbody = document.getElementById('userTableBody');
  try {
    const response = await fetch('../../api/manager/get_users.php');
    const result = await response.json();
    if (result.success) {
      allUsers = result.users;
      renderTable(allUsers);
      updateStats(allUsers);
    } else {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error: ${result.message}</td></tr>`;
    }
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Failed to connect to server</td></tr>`;
  }
}

function renderTable(users) {
  const tbody = document.getElementById('userTableBody');
  if (users.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-secondary">No users found in this department.</td></tr>`;
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td class="ps-4 py-3">
        <div class="fw-bold text-white">${u.name}</div>
        <div class="text-secondary small">${u.email}</div>
      </td>
      <td><span class="text-secondary small">${u.roll_number || 'N/A'}</span></td>
      <td>
        <span class="badge bg-${u.role === 'supervisor' ? 'info' : 'success'} bg-opacity-10 text-${u.role === 'supervisor' ? 'info' : 'success'} border border-${u.role === 'supervisor' ? 'info' : 'success'} border-opacity-25 px-3 rounded-pill text-capitalize">
          ${u.role}
        </span>
      </td>
      <td><span class="text-secondary small">${u.program_name || 'N/A'}</span></td>
      <td class="pe-4">
        ${u.role === 'student' ? `
          <span class="badge bg-${getStatusColor(u.proposal_status)} bg-opacity-10 text-${getStatusColor(u.proposal_status)} border border-${getStatusColor(u.proposal_status)} border-opacity-25 px-3 rounded-pill text-capitalize">
            ${u.proposal_status ? u.proposal_status : 'Not Submitted'}
          </span>
        ` : '--'}
      </td>
    </tr>
  `).join('');
}

function getStatusColor(status) {
  if (!status) return 'secondary';
  switch (status.toLowerCase()) {
    case 'accepted': return 'success';
    case 'pending': return 'warning';
    case 'rejected': return 'danger';
    default: return 'secondary';
  }
}

function updateStats(users) {
  document.getElementById('statTotalUsers').textContent = users.length;
  document.getElementById('statStudents').textContent = users.filter(u => u.role === 'student').length;
  document.getElementById('statSupervisors').textContent = users.filter(u => u.role === 'supervisor').length;
  document.getElementById('statActive').textContent = users.length;
}

document.getElementById('userSearch').addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase();
  const role = document.getElementById('roleFilter').value;
  const filtered = allUsers.filter(u => {
    const matchesSearch = u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (u.roll_number || '').toLowerCase().includes(q);
    const matchesRole = !role || u.role === role;
    return matchesSearch && matchesRole;
  });
  renderTable(filtered);
});

document.getElementById('roleFilter').addEventListener('change', function(e) {
  document.getElementById('userSearch').dispatchEvent(new Event('input'));
});

document.addEventListener('DOMContentLoaded', () => {
  requireAuth('pbl_manager');
  loadUsersFromDb();
});
</script>
</body>
</html>


