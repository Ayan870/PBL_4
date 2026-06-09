<?php
require_once "../../helpers/auth_check.php";
requireRole('chairman');
require_once "../../config/db.php";

$chairman_name = $_SESSION['user_name'] ?? 'Chairman';
$dept_id = (int)($_GET['dept_id'] ?? 0);

$dept_name = "All Departments";
if ($dept_id > 0) {
    $res = mysqli_query($conn, "SELECT name FROM departments WHERE id = $dept_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $dept_name = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manager Assignment – PROVIA</title>
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
      <li><a class="nav-link active" href="manager-assignment.php"><i class="bi bi-person-gear me-3"></i>Assign Managers</a></li>
      <li><a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart-fill me-3"></i>Analytics</a></li>
    </ul>
  </nav>
  <div class="p-4 border-top border-secondary border-opacity-10">
    <button class="btn btn-outline-danger btn-sm w-100 rounded-3" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
  </div>
</div>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">Manager Assignment</h3>
      <p class="text-secondary mb-0">Selecting PBL Manager for <?php echo htmlspecialchars($dept_name); ?></p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary rounded-3"><i class="bi bi-arrow-left me-2"></i>Back to Dashboard</a>
  </div>

  <?php if ($dept_id <= 0): ?>
    <div class="row g-4">
      <?php
      $depts = mysqli_query($conn, "SELECT d.*, (SELECT u.name FROM users u WHERE u.department_id = d.id AND u.role = 'pbl_manager' LIMIT 1) as manager_name FROM departments d");
      while ($d = mysqli_fetch_assoc($depts)):
      ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-body p-4">
              <h5 class="text-white mb-2"><?php echo $d['name']; ?></h5>
              <div class="mb-4">
                <small class="text-secondary d-block">Current Manager:</small>
                <span class="<?php echo $d['manager_name'] ? 'text-success' : 'text-danger small italic'; ?>">
                  <?php echo $d['manager_name'] ?? 'Not Assigned'; ?>
                </span>
              </div>
              <a href="?dept_id=<?php echo $d['id']; ?>" class="btn btn-primary w-100 rounded-pill">Select New Manager</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="card shadow-lg">
      <div class="card-header py-3">
        <div class="row align-items-center">
          <div class="col">
            <span class="fw-bold text-white">Eligible Faculty members</span>
            <p class="text-secondary small mb-0">Choose a user to promote to PBL Manager for this department.</p>
          </div>
          <div class="col-md-4">
            <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Search by name or email..." id="userSearch">
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark bg-opacity-50">
              <tr>
                <th class="ps-4">User Details</th>
                <th>Current Role</th>
                <th class="pe-4 text-end">Action</th>
              </tr>
            </thead>
            <tbody id="potentialManagers">
              <!-- Loaded via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white">Confirm Assignment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width:60px;height:60px;font-size:2rem;"><i class="bi bi-exclamation-triangle"></i></div>
        <p class="text-secondary">Are you sure you want to appoint <strong id="modalUserName" class="text-white"></strong> as the new PBL Manager for <strong class="text-white"><?php echo htmlspecialchars($dept_name); ?></strong>?</p>
        <p class="smaller text-secondary opacity-75">Note: The current manager (if any) will be demoted to supervisor.</p>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary px-4" id="btnAssignConfirm">Confirm Appointment</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let allUsers = [];
let selectedUser = null;
const deptId = <?php echo $dept_id; ?>;

async function loadPotentialManagers() {
  if (deptId <= 0) return;
  try {
    const res = await fetch(`../../api/chairman/get_potential_managers.php?dept_id=${deptId}`);
    const data = await res.json();
    if (data.success) {
      allUsers = data.users;
      renderUsers(allUsers);
    }
  } catch (e) {}
}

function renderUsers(users) {
  const tbody = document.getElementById('potentialManagers');
  if (users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-secondary">No eligible users found in this department.</td></tr>';
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td class="ps-4 py-3">
        <div class="fw-bold text-white">${u.name}</div>
        <div class="text-secondary small">${u.email}</div>
      </td>
      <td>
        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 rounded-pill text-capitalize">
          ${u.role.replace('_', ' ')}
        </span>
      </td>
      <td class="pe-4 text-end">
        <button class="btn btn-sm btn-primary rounded-pill px-4" onclick="openConfirm(${u.id}, '${u.name}')" ${u.role === 'pbl_manager' ? 'disabled' : ''}>
          ${u.role === 'pbl_manager' ? 'Current Manager' : 'Assign'}
        </button>
      </td>
    </tr>
  `).join('');
}

function openConfirm(id, name) {
  selectedUser = id;
  document.getElementById('modalUserName').textContent = name;
  new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

document.getElementById('btnAssignConfirm').addEventListener('click', async () => {
  if (!selectedUser) return;
  const btn = document.getElementById('btnAssignConfirm');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';

  try {
    const res = await fetch('../../api/chairman/assign_manager.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: selectedUser, department_id: deptId })
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = 'dashboard.php';
    } else {
      alert(data.message);
      btn.disabled = false;
      btn.textContent = 'Confirm Appointment';
    }
  } catch (e) {
    alert('An error occurred');
    btn.disabled = false;
    btn.textContent = 'Confirm Appointment';
  }
});

document.getElementById('userSearch')?.addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase();
  const filtered = allUsers.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
  renderUsers(filtered);
});

document.addEventListener('DOMContentLoaded', loadPotentialManagers);
</script>
<?php include_once "../../pages/shared/chat_init.php"; ?>
</body>
</html>

