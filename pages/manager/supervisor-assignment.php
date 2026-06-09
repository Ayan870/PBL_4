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
  <title>Supervisor Assignment – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body{background:#0f172a; color: #e2e8f0;}
    .sidebar{min-height:100vh;background:#1e293b;border-right:1px solid #334155;width:240px;position:fixed;top:0;left:0; z-index: 100;}
    .sidebar .nav-link{color:#94a3b8;font-size:0.9rem;padding:12px 16px;border-radius:8px;margin:4px 12px;transition:all 0.2s;}
    .sidebar .nav-link:hover{background:rgba(79, 70, 229, 0.1);color:#818cf8;}
    .sidebar .nav-link.active{background:#4f46e5;color:#fff;box-shadow:0 4px 12px rgba(79, 70, 229, 0.3);}
    .main{margin-left:240px;padding:32px;min-height:100vh;}
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; overflow: hidden; }
    .card-header { background: rgba(255,255,255,0.03); border-bottom: 1px solid #334155; padding: 1.25rem 1.5rem; }
    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
  </style>
</head>
<body>

<?php require_once "../../includes/sidebar.php"; ?>

<div class="main">
  <div class="mb-5">
    <h3 class="fw-bold text-white mb-1">Supervisor Assignment</h3>
    <p class="text-secondary small"><?php echo htmlspecialchars($dept_name); ?> Department • Manage faculty-class relationships</p>
  </div>

  <!-- Assignment Section -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white bg-opacity-10"><span class="fw-bold text-white small text-uppercase">Assign Supervisors (Min 1, Max 2)</span></div>
    <div class="card-body p-4">
      <form class="row g-3 align-items-end" id="assignForm" onsubmit="assignSupervisor(event)">
        <div class="col-md-3">
          <label class="form-label small fw-bold text-secondary">Program</label>
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midProgram" required onchange="updateClassAndSubject()">
            <option value="">Select Program</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold text-secondary">Class / Section</label>
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midClass" required>
            <option value="">Select Class</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold text-secondary">PBL Subject</label>
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midSubject" required>
            <option value="">Select Subject</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold text-secondary">Supervisor</label>
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midSupervisor" required>
            <option value="">Select Supervisor</option>
          </select>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary btn-sm px-4 fw-bold" type="submit" id="submitBtn"><i class="bi bi-plus-circle me-1"></i> Add Assignment</button>
        </div>
      </form>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
        <h6 class="small fw-bold text-secondary text-uppercase mb-3">Current Assignments</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead>
              <tr class="text-secondary smaller text-uppercase" style="font-size: 0.7rem;">
                <th>Program</th><th>Class</th><th>Subject</th><th>Supervisors</th><th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="midAssignTbody">
              <tr><td colspan="5" class="text-center py-3 text-secondary small">No assignments found.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let configData = null;

async function loadConfigData() {
  try {
    const res = await fetch('../../api/manager/get_eval_config_data.php');
    if (!res.ok) throw new Error('API Error');
    const text = await res.text();
    try {
        configData = JSON.parse(text);
    } catch(e) {
        console.error('JSON Parse Error. Raw response:', text);
        alert('Data format error. Please check server logs.');
        return;
    }
    if(configData.success) {
      populateDropdowns();
      renderAssignments();
    } else {
      alert(configData.message);
    }
  } catch(e) {
    console.error(e);
    alert('Failed to load configuration data.');
  }
}

function populateDropdowns() {
  const progSel = document.getElementById('midProgram');
  const supSel = document.getElementById('midSupervisor');
  
  progSel.innerHTML = '<option value="">Select Program</option>';
  configData.programs.forEach(p => {
    progSel.innerHTML += `<option value="${p.id}">${p.name}</option>`;
  });
  
  supSel.innerHTML = '<option value="">Select Supervisor</option>';
  configData.supervisors.forEach(s => {
    supSel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
  });
}

function updateClassAndSubject() {
  const progId = document.getElementById('midProgram').value;
  const classSel = document.getElementById('midClass');
  const subSel = document.getElementById('midSubject');
  
  classSel.innerHTML = '<option value="">Select Class</option>';
  subSel.innerHTML = '<option value="">Select Subject</option>';
  
  if(!progId) return;
  
  // Populate Classes
  const prog = configData.programs.find(p => p.id == progId);
  if(prog) {
    prog.classes.forEach(c => {
      classSel.innerHTML += `<option value="${c.id}" data-semester="${c.semester_id}">${c.session} (Sem ${c.semester_number}) - ${c.section}</option>`;
    });
  }

  // Populate Subjects from Master List
  configData.all_subjects.forEach(s => {
      subSel.innerHTML += `<option value="${s.title}">${s.title}</option>`;
  });
}

function filterSubjects() {
  // We no longer need to filter subjects by semester on the frontend if we show all master subjects
  // But we could if we wanted to. The user said "decided here", implying they pick from the list.
}

function renderAssignments() {
  const tbody = document.getElementById('midAssignTbody');
  if(!configData.assignments || configData.assignments.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-secondary small">No assignments found.</td></tr>';
    return;
  }
  
  const groups = {};
  configData.assignments.forEach(a => {
    const key = `${a.class_id}_${a.pbl_subject_id}`;
    if(!groups[key]) groups[key] = { ...a, supervisors: [] };
    groups[key].supervisors.push({ id: a.id, name: a.supervisor_name });
  });

  tbody.innerHTML = Object.values(groups).map(g => `
    <tr class="small">
      <td><span class="badge bg-primary text-white">${g.program_name}</span></td>
      <td>${g.session} (${g.section})</td>
      <td>${g.subject_title}</td>
      <td>
        ${g.supervisors.map(s => `
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-info text-white">${s.name}</span>
            <i class="bi bi-x-circle text-danger cursor-pointer" onclick="deleteAssignment(${s.id})" title="Remove"></i>
          </div>
        `).join('')}
      </td>
      <td class="text-end">
        <span class="text-secondary smaller">${g.supervisors.length}/2 Assigned</span>
      </td>
    </tr>
  `).join('');
}

async function assignSupervisor(e) {
  e.preventDefault();
  const payload = {
    class_id: document.getElementById('midClass').value,
    supervisor_id: document.getElementById('midSupervisor').value,
    subject_title: document.getElementById('midSubject').value
  };
  
  try {
    const res = await fetch('../../api/manager/assign_supervisor.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await res.json();
    if(result.success) {
      loadConfigData();
      alert('Assigned successfully!');
    } else {
      alert(result.message);
    }
  } catch(e) { alert('Failed to assign'); }
}

async function deleteAssignment(id) {
  if(!confirm('Remove this assignment?')) return;
  try {
    const res = await fetch('../../api/manager/delete_assignment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if(result.success) loadConfigData();
  } catch(e) {}
}

document.addEventListener('DOMContentLoaded', loadConfigData);
</script>
<?php include_once "../shared/chat_init.php"; ?>
</body>
</html>


