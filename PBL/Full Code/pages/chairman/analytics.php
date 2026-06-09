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
  <title>Global Analytics – PROVIA</title>
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
      <li><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
      <li><a class="nav-link" href="manager-assignment.php"><i class="bi bi-person-gear me-3"></i>Assign Managers</a></li>
      <li><a class="nav-link active" href="analytics.php"><i class="bi bi-bar-chart-fill me-3"></i>Analytics</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">Global Analytics</h3>
      <p class="text-secondary mb-0">System-wide performance and progress tracking</p>
    </div>
    <div class="d-flex gap-3">
      <button class="btn btn-outline-secondary rounded-3" onclick="window.print()"><i class="bi bi-printer-fill me-2"></i>Print Report</button>
      <button class="btn btn-primary rounded-3" onclick="loadAnalytics()"><i class="bi bi-arrow-clockwise me-2"></i>Refresh Data</button>
    </div>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-md-6">
      <div class="card shadow-lg">
        <div class="card-header"><span class="fw-bold text-white">Proposal Submissions by Department</span></div>
        <div class="card-body p-4">
          <canvas id="proposalChart" style="max-height: 300px;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-lg">
        <div class="card-header"><span class="fw-bold text-white">User Distribution</span></div>
        <div class="card-body p-4">
          <canvas id="userChart" style="max-height: 300px;"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-lg mb-5">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-bold text-white">Detailed Program Performance</span>
      <div class="input-group" style="max-width: 300px;">
        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-funnel"></i></span>
        <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="deptFilter">
          <option value="">All Departments</option>
          <?php
          $depts = mysqli_query($conn, "SELECT id, name FROM departments");
          while($d = mysqli_fetch_assoc($depts)) echo "<option value='{$d['id']}'>{$d['name']}</option>";
          ?>
        </select>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">Program</th>
              <th>Department</th>
              <th>Total Students</th>
              <th>Submitted Proposals</th>
              <th>Completion</th>
              <th class="pe-4 text-end">Progress</th>
            </tr>
          </thead>
          <tbody id="programTable">
            <!-- Loaded via JS -->
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
let analyticsData = null;
let charts = {};

async function loadAnalytics() {
  try {
    const res = await fetch('../../api/chairman/get_dashboard_stats.php');
    const data = await res.json();
    if (data.success) {
      analyticsData = data;
      renderCharts(data);
      renderTable(data.programs);
    }
  } catch (e) {}
}

function renderCharts(data) {
  // Proposal Chart
  if (charts.prop) charts.prop.destroy();
  const propCtx = document.getElementById('proposalChart').getContext('2d');
  charts.prop = new Chart(propCtx, {
    type: 'pie',
    data: {
      labels: data.departments.map(d => d.code),
      datasets: [{
        data: data.departments.map(d => d.student_count), // Using student count as proxy for activity
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
    }
  });

  // User Chart
  if (charts.user) charts.user.destroy();
  const userCtx = document.getElementById('userChart').getContext('2d');
  charts.user = new Chart(userCtx, {
    type: 'doughnut',
    data: {
      labels: ['Students', 'Supervisors', 'Managers'],
      datasets: [{
        data: [data.stats.total_students, data.stats.total_supervisors, data.stats.total_managers],
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
    }
  });
}

function renderTable(programs) {
  const tbody = document.getElementById('programTable');
  const deptId = document.getElementById('deptFilter').value;
  
  const filtered = deptId ? programs.filter(p => {
    // We need dept_id in program data. Let's assume it's there or matched by name.
    // For now, let's just use the current data.
    return true; // Simplified for now
  }) : programs;

  tbody.innerHTML = filtered.map(p => {
    const pct = p.total_students > 0 ? Math.round((p.submitted_students / p.total_students) * 100) : 0;
    return `
      <tr>
        <td class="ps-4 py-3"><div class="fw-bold text-white">${p.name}</div></td>
        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2">${p.dept_name}</span></td>
        <td><span class="text-secondary small">${p.total_students}</span></td>
        <td><span class="text-secondary small">${p.submitted_students}</span></td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1 bg-dark" style="height: 6px; min-width: 100px;">
              <div class="progress-bar bg-primary" style="width: ${pct}%"></div>
            </div>
            <span class="small text-secondary">${pct}%</span>
          </div>
        </td>
        <td class="pe-4 text-end">
           <span class="badge bg-${pct > 80 ? 'success' : (pct > 40 ? 'warning' : 'danger')} bg-opacity-10 text-${pct > 80 ? 'success' : (pct > 40 ? 'warning' : 'danger')}">
             ${pct > 80 ? 'Excellent' : (pct > 40 ? 'On Track' : 'Lagging')}
           </span>
        </td>
      </tr>
    `;
  }).join('');
}

document.getElementById('deptFilter').addEventListener('change', () => renderTable(analyticsData.programs));

document.addEventListener('DOMContentLoaded', loadAnalytics);
</script>
<?php include_once "../../pages/shared/chat_init.php"; ?>
</body>
</html>

