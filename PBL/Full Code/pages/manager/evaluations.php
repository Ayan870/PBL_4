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
  <title>Evaluation Center – PROVIA</title>
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
    
    /* Selection Cards */
    .selection-hub {
      display: flex;
      gap: 2rem;
      justify-content: center;
      align-items: center;
      min-height: 60vh;
    }
    .hub-card {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 24px;
      padding: 3rem 2rem;
      width: 320px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    .hub-card:hover {
      transform: translateY(-10px);
      border-color: #6366f1;
      box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .hub-card i {
      font-size: 4rem;
      margin-bottom: 1.5rem;
      display: block;
      background: linear-gradient(45deg, #6366f1, #a855f7);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hub-card h3 {
      font-weight: 700;
      margin-bottom: 1rem;
    }
    .hub-card p {
      color: #94a3b8;
      font-size: 0.95rem;
      line-height: 1.5;
    }
    .hub-card .badge {
      position: absolute;
      top: 1rem;
      right: 1.5rem;
      padding: 0.5rem 1rem;
      border-radius: 50px;
    }

    /* Views */
    .view-container { display: none; }
    .view-container.active { display: block; animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; overflow: hidden; }
    .card-header { background: rgba(255,255,255,0.03); border-bottom: 1px solid #334155; padding: 1.25rem 1.5rem; }
    
    .btn-back { margin-bottom: 2rem; color: #94a3b8; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 500; transition: color 0.2s; }
    .btn-back:hover { color: #fff; }

    @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.selection-hub { flex-direction: column; }}
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
      <li><a class="nav-link active" href="evaluations.php"><i class="bi bi-star-fill me-3"></i>Evaluations</a></li>
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
  <!-- Selection View -->
  <div id="selectionHub" class="view-container active">
    <div class="mb-5 text-center">
      <h2 class="fw-bold text-white">Evaluation Center</h2>
      <p class="text-secondary"><?php echo htmlspecialchars($dept_name); ?> Department • Select an evaluation track to manage</p>
    </div>
    
    <div class="selection-hub">
      <div class="hub-card" onclick="switchView('midTermView')">
        <span class="badge bg-primary text-white border border-white border-opacity-25 fw-bold px-3">INTERNAL</span>
        <i class="bi bi-journal-check"></i>
        <h3>Mid-Term</h3>
        <p>Monitor supervisor-submitted marks, progress reports, and feedback for all groups.</p>
      </div>

      <div class="hub-card" onclick="switchView('finalView')">
        <span class="badge bg-info text-white border border-white border-opacity-25 fw-bold px-3">EXTERNAL</span>
        <i class="bi bi-award"></i>
        <h3>Final Evaluation</h3>
        <p>Manage external evaluator sessions, trigger evaluation cycles, and view final scores.</p>
      </div>
    </div>
  </div>

  <!-- Mid-Term View -->
  <div id="midTermView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    

    <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
      <div>
        <h3 class="fw-bold text-white mb-1">Mid-Term Monitoring</h3>
        <p class="text-secondary small">Overview of internal evaluations submitted by supervisors</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary px-3" data-bs-toggle="collapse" data-bs-target="#scheduleCollapse"><i class="bi bi-calendar-plus me-1"></i>Schedule Session</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="loadMidTermData()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
      </div>
    </div>

    <!-- Scheduling Form (Collapsed by default) -->
    <div class="collapse mb-4" id="scheduleCollapse">
      <div class="card shadow-sm border-0 bg-dark bg-opacity-25">
        <div class="card-header"><span class="fw-bold text-white small text-uppercase">New Monitoring Session</span></div>
        <div class="card-body p-4">
          <form class="row g-3 align-items-end" id="midScheduleForm" onsubmit="scheduleMidSession(event)">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">Semester Track</label>
              <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midSemId" required></select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">Mid Evaluator (Supervisor)</label>
              <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-white" id="midEvalId" required></select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">Evaluation Date</label>
              <input type="date" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" id="midEvalDate" required/>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">Start Time</label>
              <input type="time" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" id="midStartTime" required/>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">End Time</label>
              <input type="time" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" id="midEndTime" required/>
            </div>
            <div class="col-md-4 d-flex justify-content-end">
              <button class="btn btn-primary btn-sm px-4 fw-bold w-100" type="submit" id="midSchedBtn"><i class="bi bi-calendar-check me-1"></i> Schedule Session</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold text-white small text-uppercase">Scheduled Sessions</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                  <tr class="text-secondary smaller text-uppercase" style="font-size: 0.7rem;">
                    <th class="ps-4">Evaluator</th><th>Semester</th><th>Date</th><th>Window</th><th class="text-end pe-4">Status</th>
                  </tr>
                </thead>
                <tbody id="midSessionsTbody">
                  <tr><td colspan="5" class="text-center py-3 text-secondary small">No sessions scheduled.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr class="text-secondary small text-uppercase">
                <th class="ps-4">Group</th>
                <th>Subject</th>
                <th>Evaluator</th>
                <th class="text-center">Progress</th>
                <th class="text-center">Marks</th>
                <th class="text-end pe-4">Date</th>
              </tr>
            </thead>
            <tbody id="midTermTbody">
              <tr><td colspan="6" class="text-center py-5 text-secondary">Loading evaluation data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Final View -->
  <div id="finalView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    <div class="d-flex justify-content-between align-items-center mb-5">
      <div>
        <h3 class="mb-1 fw-bold text-white">Final Evaluation Management</h3>
        <p class="text-secondary small">Setup external evaluators and monitor live results feed</p>
      </div>
      <div class="text-end">
        <div id="liveClock" class="fw-medium text-secondary small mb-1">Loading...</div>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill">External Session Active</span>
      </div>
    </div>

    <div class="row g-4">
      <!-- Session Setup -->
      <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
          <div class="card-header"><span class="fw-bold text-white">Trigger New Session</span></div>
          <div class="card-body p-4">
            <form id="triggerForm" onsubmit="triggerEval(event)">
              <div class="mb-3">
                <label class="form-label text-secondary small fw-bold">Select Session/Semester track</label>
                <select class="form-select bg-dark border-secondary border-opacity-25 text-white" id="semesterId" required>
                  <option value="">Loading semesters...</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label text-secondary small fw-bold">Evaluator Name</label>
                <input class="form-control bg-dark border-secondary border-opacity-25 text-white" id="evalName" placeholder="Dr. John Doe" required/>
              </div>
              <div class="mb-3">
                <label class="form-label text-secondary small fw-bold">Evaluator Email</label>
                <input type="email" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="evalEmail" placeholder="evaluator@uni.edu" required/>
              </div>
              <div class="mb-4">
                <label class="form-label text-secondary small fw-bold">Access Password</label>
                <div class="input-group">
                  <input type="password" class="form-control bg-dark border-secondary border-opacity-25 text-white" id="evalPassword" placeholder="Min 6 characters" required minlength="6"/>
                  <button class="btn btn-outline-secondary border-opacity-25" type="button" onclick="togglePass()"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <button class="btn btn-primary w-100 py-2 fw-bold" type="submit" id="triggerBtn">
                <i class="bi bi-lightning-charge-fill me-1"></i> Trigger Evaluation
              </button>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold text-white">Active Evaluators</span>
            <span class="badge bg-success rounded-pill" id="activeCount">0</span>
          </div>
          <div id="sessionsList" class="list-group list-group-flush bg-transparent">
            <div class="p-4 text-center text-secondary small">No active sessions.</div>
          </div>
        </div>
      </div>

      <!-- Feed -->
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold text-white">Live Results Feed</span>
            <button class="btn btn-link btn-sm text-secondary text-decoration-none p-0" onclick="loadFinalData()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
          </div>
          <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr class="text-secondary small text-uppercase">
                  <th class="ps-4">Group / Project</th>
                  <th>Evaluator</th>
                  <th class="text-center">Score (20)</th>
                  <th class="text-end pe-4">Date</th>
                </tr>
              </thead>
              <tbody id="finalEvalTbody">
                <tr><td colspan="4" class="text-center text-secondary py-5">No evaluation data found.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let configData = null;

function switchView(viewId) {
  document.querySelectorAll('.view-container').forEach(v => v.classList.remove('active'));
  document.getElementById(viewId).classList.add('active');
  
  if(viewId === 'midTermView') {
    loadMidTermData();
    loadConfigData();
  }
  if(viewId === 'finalView') {
    loadSemesters();
    loadSessions();
    loadFinalData();
    updateClock();
  }
}

// Mid-Term Logic
async function loadConfigData() {
  try {
    const res = await fetch('../../api/manager/get_eval_config_data.php');
    configData = await res.json();
    if(configData.success) {
      populateDropdowns();
      renderAssignments();
    }
  } catch(e) {}
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
  
  const prog = configData.programs.find(p => p.id == progId);
  if(prog) {
    prog.classes.forEach(c => {
      classSel.innerHTML += `<option value="${c.id}">${c.session} (Sem ${c.semester_number}) - ${c.section}</option>`;
    });
    prog.subjects.forEach(s => {
      subSel.innerHTML += `<option value="${s.id}">${s.title}</option>`;
    });
  }
}

function renderAssignments() {
  const tbody = document.getElementById('midAssignTbody');
  if(!configData.assignments || configData.assignments.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-secondary small">No assignments found.</td></tr>';
    return;
  }
  
  // Group assignments by Class and Subject to show multiple supervisors
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
    subject_id: document.getElementById('midSubject').value
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

async function loadMidTermData() {
  const tbody = document.getElementById('midTermTbody');
  try {
    loadMidSessions();
    const res = await fetch('../../api/manager/get_mid_eval_summary.php');
    const data = await res.json();
    if(data.success) {
      if(data.evaluations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-secondary">No mid-term evaluations found.</td></tr>';
        return;
      }
      tbody.innerHTML = data.evaluations.map(e => {
        const isRunning = e.status === 'Running';
        const date = e.evaluation_date ? new Date(e.evaluation_date).toLocaleDateString() : '--';
        return `
        <tr>
          <td class="ps-4">
            <div class="fw-bold text-white">${e.program_name} - ${e.section}</div>
            <div class="text-secondary small" style="font-size:0.75rem;">${e.group_name === 'N/A' ? 'Monitoring Session' : e.group_name}</div>
          </td>
          <td><span class="text-secondary small">${e.subject_title}</span></td>
          <td><span class="text-white small">${e.supervisor_name}</span></td>
          <td class="text-center">
            <span class="badge ${isRunning ? 'bg-warning' : 'bg-success'} text-white px-3 rounded-pill shadow-sm" style="font-size: 0.7rem;">
              <i class="bi ${isRunning ? 'bi-clock-history' : 'bi-check-circle-fill'} me-1"></i>
              ${isRunning ? 'Mid Evaluation Ongoing' : 'Completed'}
            </span>
          </td>
          <td class="text-center"><span class="fw-bold ${isRunning ? 'text-secondary' : 'text-success'}">${e.marks}/5</span></td>
          <td class="text-end pe-4">
            <div class="text-secondary small">${date}</div>
          </td>
        </tr>
      `;}).join('');
    }
  } catch(e) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Failed to load data.</td></tr>'; }
}

async function scheduleMidSession(e) {
  e.preventDefault();
  const btn = document.getElementById('midSchedBtn');
  btn.disabled = true;
  const payload = {
    semester_id: document.getElementById('midSemId').value,
    evaluator_id: document.getElementById('midEvalId').value,
    eval_date: document.getElementById('midEvalDate').value,
    start_time: document.getElementById('midStartTime').value,
    end_time: document.getElementById('midEndTime').value
  };
  try {
    const res = await fetch('../../api/manager/create_mid_session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success) {
      alert('Session scheduled!');
      document.getElementById('midScheduleForm').reset();
      loadMidSessions();
    } else alert(data.message);
  } catch(e) {}
  finally { btn.disabled = false; }
}

async function loadMidSessions() {
  try {
    const res = await fetch('../../api/manager/get_mid_sessions.php');
    const data = await res.json();
    if(data.success) {
      const tbody = document.getElementById('midSessionsTbody');
      if(data.sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-secondary small">No sessions scheduled.</td></tr>';
        return;
      }
      const now = new Date();
      tbody.innerHTML = data.sessions.map(s => {
        const evalDate = new Date(s.eval_date + 'T' + s.start_time);
        const endDate = new Date(s.eval_date + 'T' + s.end_time);
        const isCurrent = now >= evalDate && now <= endDate;
        const isPast = now > endDate;
        const isTerminated = s.status === 'terminated';

        let status = '<span class="badge bg-secondary">Upcoming</span>';
        if(isTerminated) status = '<span class="badge bg-danger">Terminated</span>';
        else if(isCurrent) status = '<span class="badge bg-success">Active Now</span>';
        else if(isPast) status = '<span class="badge bg-dark">Expired</span>';

        const actionBtn = (!isTerminated && !isPast) 
          ? `<button class="btn btn-sm btn-outline-danger px-2 py-0" style="font-size:0.65rem;" onclick="terminateMidSession(${s.id})">Terminate</button>` 
          : '';

        return `
          <tr>
            <td class="ps-4"><div class="fw-bold text-white small">${s.evaluator_name}</div></td>
            <td><div class="text-secondary smaller">${s.session} ${s.year} (Sem ${s.number})</div></td>
            <td><div class="text-secondary smaller">${s.eval_date}</div></td>
            <td><div class="text-secondary smaller">${s.start_time} - ${s.end_time}</div></td>
            <td class="text-end pe-4 d-flex align-items-center justify-content-end gap-2">${status} ${actionBtn}</td>
          </tr>
        `;
      }).join('');
    }
  } catch(e) {}
}

async function terminateMidSession(id) {
  if(!confirm('Terminate this monitoring session immediately?')) return;
  try {
    const res = await fetch('../../api/manager/terminate_mid_session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if(data.success) loadMidSessions();
  } catch(e) {}
}

async function loadMidSchedFormOptions() {
  try {
    const res = await fetch('../../api/manager/get_eval_config_data.php');
    const data = await res.json();
    if(data.success) {
      const evalSelect = document.getElementById('midEvalId');
      evalSelect.innerHTML = '<option value="">Choose Evaluator...</option>';
      data.supervisors.forEach(s => {
        evalSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
      });
    }
    const res2 = await fetch('../../api/manager/get_semesters.php');
    const data2 = await res2.json();
    if(data2.success) {
      const semSelect = document.getElementById('midSemId');
      semSelect.innerHTML = '<option value="">Choose Semester Track...</option>';
      data2.semesters.forEach(s => {
        semSelect.innerHTML += `<option value="${s.id}">${s.session} ${s.year} (Sem ${s.number})</option>`;
      });
    }
  } catch(e) {}
}

// Final Evaluation Logic (Integrated from final-evaluation.php)
function updateClock() {
  const now = new Date();
  const clock = document.getElementById('liveClock');
  if(clock) clock.textContent = now.toLocaleString();
}

function togglePass() {
  const p = document.getElementById('evalPassword');
  p.type = p.type === 'password' ? 'text' : 'password';
}

async function loadSemesters() {
  try {
    const res = await fetch('../../api/manager/get_semesters.php');
    const data = await res.json();
    if(data.success) {
      const select = document.getElementById('semesterId');
      select.innerHTML = '<option value="">Choose Semester Track...</option>';
      data.semesters.forEach(s => {
        select.innerHTML += `<option value="${s.id}">${s.session} ${s.year} (Sem ${s.number})</option>`;
      });
    }
  } catch(e) {}
}

async function triggerEval(e) {
  e.preventDefault();
  const btn = document.getElementById('triggerBtn');
  const oldText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

  const payload = {
    semester_id: document.getElementById('semesterId').value,
    name: document.getElementById('evalName').value,
    email: document.getElementById('evalEmail').value,
    password: document.getElementById('evalPassword').value
  };

  try {
    const res = await fetch('../../api/manager/trigger_final_eval.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success) {
      alert('Evaluation track triggered successfully!');
      document.getElementById('triggerForm').reset();
      loadSessions();
    } else {
      alert('Error: ' + data.message);
    }
  } catch(e) { alert('Request failed'); }
  finally {
    btn.disabled = false;
    btn.innerHTML = oldText;
  }
}

async function loadSessions() {
  try {
    const res = await fetch('../../api/manager/get_final_eval_sessions.php');
    const data = await res.json();
    if(data.success) {
      const list = document.getElementById('sessionsList');
      const activeCount = document.getElementById('activeCount');
      let active = 0;
      
      if(data.sessions.length === 0) {
        list.innerHTML = '<div class="p-4 text-center text-secondary small">No active sessions.</div>';
        activeCount.textContent = '0';
        return;
      }
      
      list.innerHTML = '';
      data.sessions.forEach(s => {
        if(s.status === 'running') active++;
        const statusBadge = s.status === 'running' ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-secondary">Closed</span>';
        const actionBtn = s.status === 'running' 
          ? `<button class="btn btn-sm btn-outline-danger px-3 py-1" onclick="closeSession(${s.id})">Close</button>` 
          : `<span class="text-secondary small">Inactive</span>`;
        
        list.innerHTML += `
          <div class="list-group-item bg-transparent border-secondary border-opacity-10 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="fw-bold text-white small mb-0">${s.evaluator_name}</div>
                <div class="text-secondary" style="font-size:0.7rem;">${s.evaluator_email}</div>
              </div>
              ${statusBadge}
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <small class="text-primary">${s.session} ${s.year}</small>
              ${actionBtn}
            </div>
          </div>
        `;
      });
      activeCount.textContent = active;
    }
  } catch(e) {}
}

async function closeSession(id) {
  if(!confirm('Close this evaluation session?')) return;
  try {
    const res = await fetch('../../api/manager/close_final_eval.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await res.json();
    if(data.success) loadSessions();
  } catch(e) {}
}

async function loadFinalData() {
  const tbody = document.getElementById('finalEvalTbody');
  try {
    const res = await fetch('../../api/manager/get_final_eval_data.php');
    const data = await res.json();
    if(data.success) {
      if(data.evaluations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-5">No final evaluations received yet.</td></tr>';
        return;
      }
      tbody.innerHTML = data.evaluations.map(e => `
        <tr>
          <td class="ps-4">
            <div class="fw-bold text-white">${e.group_name}</div>
            <div class="text-secondary small" style="font-size:0.7rem;">${e.subject_title}</div>
          </td>
          <td>
            <div class="small text-white">${e.evaluator_name}</div>
            <div class="text-secondary" style="font-size:0.65rem;">External Expert</div>
          </td>
          <td class="text-center">
            <span class="fs-5 fw-bold text-success">${e.marks_out_of_20}</span><span class="text-secondary small">/20</span>
          </td>
          <td class="text-end pe-4">
            <div class="small text-secondary">${new Date(e.created_at).toLocaleDateString()}</div>
          </td>
        </tr>
      `).join('');
    }
  } catch(e) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load feed.</td></tr>'; }
}

loadMidSchedFormOptions();
setInterval(updateClock, 1000);
</script>
<?php include_once "../shared/chat_init.php"; ?>
</body>
</html>


