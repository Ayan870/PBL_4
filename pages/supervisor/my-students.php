<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";
$pageTitle = "My Students – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$supervisor_id = $_SESSION['user_id'];
$is_mid_evaluator = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM mid_eval_sessions WHERE evaluator_id = $supervisor_id AND eval_date = CURDATE() AND CURTIME() BETWEEN start_time AND end_time AND status = 'active' LIMIT 1")) > 0;
?>




<div class="main">
  <div class="mb-5">
    <h3 class="mb-1 fw-bold text-white">My Students</h3>
    <p class="text-secondary mb-0">View and manage students in your assigned classes</p>
  </div>

  <div class="card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="input-group w-50">
        <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Search by name or roll number..." id="searchStudent" oninput="renderStudents()">
      </div>
      <div id="countLabel" class="text-secondary small"></div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">Student Name</th>
              <th>Roll Number</th>
              <th>Program</th>
              <th class="pe-4 text-end">Contact</th>
            </tr>
          </thead>
          <tbody id="studentsTbody">
            <tr><td colspan="4" class="text-center py-5 text-secondary">Loading students...</td></tr>
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
let allStudents = [];

async function loadStudents() {
  const tbody = document.getElementById('studentsTbody');
  try {
    const res = await fetch('../../api/supervisor/get_students.php');
    const data = await res.json();
    if (data.success) {
      allStudents = data.students;
      renderStudents();
    } else {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Error: ${data.message}</td></tr>`;
    }
  } catch (e) { 
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Connection error. Please refresh.</td></tr>`;
  }
}

function renderStudents() {
  const tbody = document.getElementById('studentsTbody');
  const q = document.getElementById('searchStudent').value.toLowerCase();
  
  const filtered = allStudents.filter(s => 
    (s.name || '').toLowerCase().includes(q) || (s.roll_number || '').toLowerCase().includes(q)
  );

  document.getElementById('countLabel').textContent = `${filtered.length} student(s) found`;

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-secondary">No students found.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(s => `
    <tr>
      <td class="ps-4 py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">${s.name.charAt(0).toUpperCase()}</div>
          <div>
            <div class="text-white fw-bold">${s.name}</div>
            <div class="text-secondary small" style="font-size:0.7rem;">${s.email}</div>
          </div>
        </div>
      </td>
      <td><span class="text-secondary small">${s.roll_number}</span></td>
      <td><span class="badge bg-primary text-white px-3 rounded-pill shadow-sm" style="font-size: 0.7rem;">${s.program_name}</span></td>
      <td class="pe-4 text-end">
        <a href="javascript:void(0)" onclick="if(window.pblChat) window.pblChat.selectContactById(${s.id})" class="btn btn-sm btn-outline-primary rounded-circle ms-1" title="Send Message"><i class="bi bi-chat-dots"></i></a>
      </td>
    </tr>
  `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
  requireAuth('supervisor');
  loadStudents();
});
</script>
<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

