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
  <title>Results & Analytics – PROVIA</title>
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
    @media print{.sidebar{display:none;}.main{margin-left:0;padding:0;}.stat-card{border:1px solid #ccc !important;}}
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
      <li><a class="nav-link" href="proposals.php"><i class="bi bi-file-earmark-text-fill me-3"></i>All Proposals</a></li>
      <li><a class="nav-link" href="supervisor-assignment.php"><i class="bi bi-person-badge-fill me-3"></i>Supervisor Assignment</a></li>
      <li><a class="nav-link" href="evaluations.php"><i class="bi bi-star-fill me-3"></i>Evaluations</a></li>
      <li><a class="nav-link active" href="analytics.php"><i class="bi bi-bar-chart-fill me-3"></i>Analytics</a></li>
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
      <h3 class="mb-1 fw-bold text-white">Department Analytics</h3>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item text-secondary"><?php echo htmlspecialchars($dept_name); ?> Department</li>
          <li class="breadcrumb-item active text-primary" aria-current="page">Performance Reports</li>
        </ol>
      </nav>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary rounded-3" onclick="loadAnalytics()"><i class="bi bi-arrow-clockwise me-2"></i>Refresh</button>
      <button class="btn btn-primary rounded-3" onclick="window.print()"><i class="bi bi-printer-fill me-2"></i>Print Report</button>
    </div>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-award-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Avg. Score</div>
        <div class="fw-bold fs-2 text-white"><span id="avgScore">--</span>%</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-all"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Graded Projects</div>
        <div class="fw-bold fs-2 text-white" id="gradedCount">--</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Students</div>
        <div class="fw-bold fs-2 text-white" id="studentsCount">--</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Passing Rate</div>
        <div class="fw-bold fs-2 text-white"><span id="passRate">--</span>%</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white">Program Performance Comparison</span>
          <small class="text-secondary">Average Score (%)</small>
        </div>
        <div class="card-body p-4">
          <div style="height: 350px;">
            <canvas id="programChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-lg h-100">
        <div class="card-header">
          <span class="fw-bold text-white">Score Distribution</span>
        </div>
        <div class="card-body p-4 d-flex flex-column justify-content-center">
          <div style="height: 300px;">
            <canvas id="scoreChart"></canvas>
          </div>
          <div class="mt-4">
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-secondary">Grade A (80-100)</span>
              <span class="text-white fw-bold" id="labelA">0</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-secondary">Grade B (70-79)</span>
              <span class="text-white fw-bold" id="labelB">0</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-secondary">Grade C (50-69)</span>
              <span class="text-white fw-bold" id="labelC">0</span>
            </div>
            <div class="d-flex justify-content-between small">
              <span class="text-secondary">Below 50 (Fail)</span>
              <span class="text-white fw-bold" id="labelD">0</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let programChart, scoreChart;

document.addEventListener('DOMContentLoaded', function () {
  requireAuth('pbl_manager');
  loadAnalytics();
});

async function loadAnalytics() {
  try {
    const res = await fetch('../../api/manager/get_analytics.php');
    const data = await res.json();
    if(data.success) {
      updateStats(data.stats);
      renderProgramChart(data.programs);
      renderScoreChart(data.distribution);
    }
  } catch(e) { console.error(e); }
}

function updateStats(stats) {
  document.getElementById('avgScore').textContent = stats.avg_percentage;
  document.getElementById('gradedCount').textContent = stats.graded_count;
  document.getElementById('studentsCount').textContent = stats.total_students;
  document.getElementById('passRate').textContent = stats.pass_percentage;
}

function renderProgramChart(programs) {
  const ctx = document.getElementById('programChart').getContext('2d');
  if(programChart) programChart.destroy();
  
  programChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: programs.map(p => p.code),
      datasets: [{
        label: 'Average Score (%)',
        data: programs.map(p => p.avg),
        backgroundColor: '#6366f1',
        borderRadius: 8,
        barThickness: 35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { 
          beginAtZero: true, 
          max: 100,
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#94a3b8' }
        },
        x: { 
          grid: { display: false },
          ticks: { color: '#94a3b8' }
        }
      }
    }
  });
}

function renderScoreChart(dist) {
  const ctx = document.getElementById('scoreChart').getContext('2d');
  if(scoreChart) scoreChart.destroy();
  
  document.getElementById('labelA').textContent = dist.A;
  document.getElementById('labelB').textContent = dist.B;
  document.getElementById('labelC').textContent = dist.C;
  document.getElementById('labelD').textContent = dist.D;

  scoreChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Grade A', 'Grade B', 'Grade C', 'Fail'],
      datasets: [{
        data: [dist.A, dist.B, dist.C, dist.D],
        backgroundColor: ['#10b981', '#6366f1', '#f59e0b', '#ef4444'],
        borderWidth: 0,
        hoverOffset: 15
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      cutout: '75%'
    }
  });
}
</script>
</body>
</html>


