<?php
require_once "../../helpers/auth_check.php";
requireRole('chairman');
require_once "../../config/db.php";

$chairman_name = $_SESSION['user_name'] ?? 'Chairman';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chairman Dashboard – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body{background:#0f172a;}
    .sidebar{min-height:100vh;background:#1e293b;border-right:1px solid #334155;width:240px;position:fixed;top:0;left:0;}
    .sidebar .nav-link{color:#94a3b8;font-size:0.9rem;padding:12px 16px;border-radius:8px;margin:4px 12px;transition:all 0.2s;}
    .sidebar .nav-link:hover{background:rgba(79, 70, 229, 0.1);color:#818cf8;}
    .sidebar .nav-link.active{background:#4f46e5;color:#fff;box-shadow:0 4px 12px rgba(79, 70, 229, 0.3);}
    .main{margin-left:240px;padding:32px;min-height:100vh;}
    .profile-card{background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);border:none;border-radius:16px;color:white;overflow:hidden;}
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
        <small class="text-secondary" style="font-size: 0.7rem;">Chairman Panel</small>
      </div>
    </div>
  </div>
  <nav class="flex-grow-1 py-4">
    <ul class="nav flex-column">
      <li><a class="nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link" href="manager-assignment.php"><i class="bi bi-person-gear me-3"></i>Assign Managers</a></li>
      <li><a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart-fill me-3"></i>Analytics</a></li>
      <li class="mt-4 px-3"><hr class="text-secondary opacity-10"></li>
      <li><a class="nav-link text-danger" href="javascript:void(0)" onclick="showResetModal()"><i class="bi bi-arrow-counterclockwise me-3"></i>Reset System</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle bg-indigo-500 text-white d-flex align-items-center justify-content-center shadow-sm" style="width:40px;height:40px;background:#6366f1;font-weight:700;" id="userAvatar">CH</div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-white small text-truncate" id="userName"><?php echo htmlspecialchars($chairman_name); ?></div>
        <div class="text-secondary small text-truncate" style="font-size:0.7rem;">Chairman</div>
      </div>
    </div>
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <!-- Profile Header Card -->
  <div class="profile-card p-4 mb-5 shadow-lg position-relative">
    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1;">
      <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 border border-white border-opacity-20 shadow-sm" style="width: 80px; height: 80px; color: white;">
        <?php echo strtoupper(substr($chairman_name, 0, 1)); ?>
      </div>
      <div>
        <h2 class="mb-1 fw-bold text-white">Welcome, <?php echo htmlspecialchars($chairman_name); ?></h2>
        <p class="text-white text-opacity-75 mb-0">System-wide overview and management portal</p>
      </div>
    </div>
    <i class="bi bi-shield-check position-absolute" style="right: 30px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 8rem; opacity: 0.05; color: white; pointer-events: none;"></i>
  </div>

  <div class="row g-4 mb-5" id="overallStats">
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Students</div>
        <div class="fw-bold fs-3 text-white" id="statStudents">0</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-workspace"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Supervisors</div>
        <div class="fw-bold fs-3 text-white" id="statSupervisors">0</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">PBL Managers</div>
        <div class="fw-bold fs-3 text-white" id="statManagers">0</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Proposals</div>
        <div class="fw-bold fs-3 text-white" id="statProposals">0</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-7">
      <div class="card shadow-lg h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white">Department Progress</span>
          <a href="analytics.php" class="btn btn-link btn-sm text-primary text-decoration-none p-0 fw-medium">View Detailed Analytics</a>
        </div>
        <div class="card-body p-4">
          <canvas id="deptChart" style="max-height: 300px;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card shadow-lg h-100">
        <div class="card-header">
          <span class="fw-bold text-white">Department Managers</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-dark bg-opacity-50">
                <tr>
                  <th class="ps-4">Department</th>
                  <th>Manager</th>
                  <th class="pe-4 text-end">Action</th>
                </tr>
              </thead>
              <tbody id="deptManagerList">
                <!-- Loaded via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-5">
    <div class="card shadow-lg">
      <div class="card-header">
        <span class="fw-bold text-white">Program Submission Progress (Global)</span>
      </div>
      <div class="card-body p-4">
        <div class="row g-4" id="programProgress">
          <!-- Loaded via JS -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="background: #1e293b; border-radius: 16px;">
      <div class="modal-header border-bottom border-secondary border-opacity-10 p-4">
        <h5 class="modal-title text-white fw-bold" id="resetModalLabel">Reset System Data</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="text-center mb-4">
          <div class="display-1 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <h4 class="text-white">Are you absolutely sure?</h4>
          <p class="text-secondary">This action will permanently delete all <strong>proposals, groups, evaluations, and messages</strong>.</p>
        </div>
        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning rounded-3 small">
          <i class="bi bi-info-circle me-2"></i> Users, departments, and programs will <strong>NOT</strong> be affected.
        </div>
      </div>
      <div class="modal-footer border-top border-secondary border-opacity-10 p-4">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger rounded-pill px-4" id="confirmResetBtn" onclick="performSystemReset()">
          Yes, Reset All Data
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
async function showResetModal() {
  const modal = new bootstrap.Modal(document.getElementById('resetModal'));
  modal.show();
}

async function performSystemReset() {
  const btn = document.getElementById('confirmResetBtn');
  const originalText = btn.innerHTML;
  
  try {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Resetting...';
    
    const res = await fetch('../../api/chairman/reset_system.php');
    const text = await res.text();
    
    try {
      const data = JSON.parse(text);
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert('Error: ' + data.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    } catch (parseError) {
      console.error('JSON Parse Error:', parseError, 'Raw text:', text);
      alert('Server Error: ' + text.substring(0, 500));
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (e) {
    console.error(e);
    alert('Network Error: ' + e.message);
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

async function loadDashboard() {
  try {
    const res = await fetch('../../api/chairman/get_dashboard_stats.php');
    const data = await res.json();
    if (data.success) {
      // Update overall stats
      document.getElementById('statStudents').textContent = data.stats.total_students;
      document.getElementById('statSupervisors').textContent = data.stats.total_supervisors;
      document.getElementById('statManagers').textContent = data.stats.total_managers;
      document.getElementById('statProposals').textContent = data.stats.total_proposals;

      // Update Department Manager List
      const managerList = document.getElementById('deptManagerList');
      managerList.innerHTML = data.departments.map(d => `
        <tr>
          <td class="ps-4 py-3">
            <div class="fw-bold text-white">${d.name}</div>
            <small class="text-secondary">${d.code}</small>
          </td>
          <td>
            ${d.manager_name ? `
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-size:0.6rem;font-weight:700;">${d.manager_name[0]}</div>
                <span class="text-secondary small">${d.manager_name}</span>
              </div>
            ` : '<span class="text-danger small italic">Not Assigned</span>'}
          </td>
          <td class="pe-4 text-end">
            <a href="manager-assignment.php?dept_id=${d.id}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Manage</a>
          </td>
        </tr>
      `).join('');

      // Render Department Chart
      renderDeptChart(data.departments);

      // Render Program Progress
      const progGrid = document.getElementById('programProgress');
      progGrid.innerHTML = data.programs.map(p => {
        const pct = p.total_students > 0 ? Math.round((p.submitted_students / p.total_students) * 100) : 0;
        return `
          <div class="col-md-4">
            <div class="p-3 border border-secondary border-opacity-10 rounded-3">
              <div class="d-flex justify-content-between mb-2 small">
                <span class="text-white fw-medium">${p.name}</span>
                <span class="text-secondary">${pct}%</span>
              </div>
              <div class="progress bg-dark" style="height: 6px;">
                <div class="progress-bar bg-primary rounded-pill" style="width: ${pct}%"></div>
              </div>
              <div class="mt-2 text-secondary smaller" style="font-size: 0.7rem;">
                Dept: ${p.dept_name} | ${p.submitted_students}/${p.total_students} Students
              </div>
            </div>
          </div>
        `;
      }).join('');
    }
  } catch (e) {
    console.error(e);
  }
}

function renderDeptChart(depts) {
  const ctx = document.getElementById('deptChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: depts.map(d => d.code),
      datasets: [
        {
          label: 'Students',
          data: depts.map(d => d.student_count),
          backgroundColor: 'rgba(99, 102, 241, 0.5)',
          borderColor: '#6366f1',
          borderWidth: 1
        },
        {
          label: 'Supervisors',
          data: depts.map(d => d.supervisor_count),
          backgroundColor: 'rgba(16, 185, 129, 0.5)',
          borderColor: '#10b981',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top', labels: { color: '#94a3b8' } }
      },
      scales: {
        x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
        y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  loadDashboard();
});
</script>
<?php include_once "../../pages/shared/chat_init.php"; ?>
</body>
</html>

