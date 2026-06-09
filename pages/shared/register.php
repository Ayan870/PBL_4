<?php
require_once "../../config/db.php";

// Fetch departments
$query = "SELECT * FROM departments";
$result = mysqli_query($conn, $query);
$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}

// Fetch programs by department
$progQuery = "SELECT p.*, d.name as dept_name FROM programs p JOIN departments d ON p.department_id = d.id";
$progResult = mysqli_query($conn, $progQuery);
$programsByDept = [];
while ($row = mysqli_fetch_assoc($progResult)) {
    $programsByDept[$row['dept_name']][] = [
        'code' => $row['code'],
        'name' => $row['name']
    ];
}

// Fetch all semesters
$semQuery = "SELECT * FROM semesters ORDER BY year DESC, number ASC";
$semResult = mysqli_query($conn, $semQuery);
$semesters = [];
while ($row = mysqli_fetch_assoc($semResult)) {
    $semesters[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register – PROVIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="../../assets/css/theme-dark-purple.css" rel="stylesheet"/>
  <style>
    body { background: #0f172a; }
    .reg-card { max-width: 480px; margin: 60px auto; }
    .brand-title { color: #4f46e5; font-weight: 700; }
    .role-btn.active { background: #4f46e5; color: #fff; border-color: #4f46e5; }
    #pwStrengthBar { height: 5px; border-radius: 3px; transition: width 0.3s, background 0.3s; }
    .card { background: #1e293b; border: 1px solid #334155; }
    .role-btn { font-size: 0.8rem; padding: 8px 10px; flex: 1 1 auto; border-radius: 8px !important; }
  </style>
</head>
<body>

<div class="reg-card px-3">
  <div class="text-center mb-4">
    <i class="bi bi-mortarboard-fill fs-1 text-primary"></i>
    <h3 class="brand-title mt-2">PROVIA</h3>
    <p class="text-muted small">Create your account</p>
  </div>

  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-body p-4">
      <h5 class="card-title mb-1 text-white">Register</h5>
      <p class="text-secondary small mb-3">Join as Student, Supervisor, or PBL Manager</p>

      <div class="d-flex flex-wrap gap-2 mb-3" role="group">
        <button type="button" class="btn btn-outline-primary role-btn active" onclick="selectRole('student', this)">Student</button>
        <button type="button" class="btn btn-outline-primary role-btn" onclick="selectRole('supervisor', this)">Supervisor</button>
        <button type="button" class="btn btn-outline-primary role-btn" onclick="selectRole('pbl_manager', this)">PBL Manager</button>
        <button type="button" class="btn btn-outline-primary role-btn" onclick="selectRole('evaluator', this)">Evaluator</button>
      </div>

      <form onsubmit="handleRegister(event)" id="regForm">
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label text-secondary">First Name</label>
            <input type="text" id="regFirst" class="form-control bg-dark text-white border-secondary border-opacity-25" placeholder="Ali" required/>
          </div>
          <div class="col-6">
            <label class="form-label text-secondary">Last Name</label>
            <input type="text" id="regLast" class="form-control bg-dark text-white border-secondary border-opacity-25" placeholder="Khan" required/>
          </div>
        </div>

        <div class="mb-3" id="regIdGroup">
          <label class="form-label text-secondary">Roll Number</label>
          <input type="text" id="regStudentId" class="form-control bg-dark text-white border-secondary border-opacity-25" placeholder="Auto-generated" disabled/>
          <small class="form-text text-secondary opacity-50" style="font-size: 0.7rem;">Your roll number will be generated automatically.</small>
        </div>

        <div class="mb-3">
          <label class="form-label text-secondary">Department</label>
          <select id="regDept" class="form-select bg-dark text-white border-secondary border-opacity-25">
            <option value="">Select Department</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3" id="regProgramGroup">
          <label class="form-label text-secondary">Program</label>
          <select id="regProgram" class="form-select bg-dark text-white border-secondary border-opacity-25">
            <option value="">Select Program</option>
          </select>
        </div>

        <!-- NEW: Enrolment & Semester Selection -->
        <div class="mb-3" id="regSemesterGroup">
          <label class="form-label text-secondary">Enrolment Year</label>
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label text-secondary opacity-50 mb-1" style="font-size: 0.7rem;">Enrolment Year</label>
              <select id="regYear" class="form-select bg-dark text-white border-secondary border-opacity-25" required>
                <?php for($y=2025; $y>=2020; $y--): ?>
                  <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label text-secondary opacity-50 mb-1" style="font-size: 0.7rem;">Intake Session</label>
              <select id="regSession" class="form-select bg-dark text-white border-secondary border-opacity-25" required>
                <option value="Fall">Fall</option>
                <option value="Spring">Spring</option>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label text-secondary opacity-50 mb-1" style="font-size: 0.7rem;">Current Sem</label>
              <select id="regSemesterNum" class="form-select bg-dark text-white border-secondary border-opacity-25" required>
                <?php for($i=1; $i<=8; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?><?php 
                    $suffix = ['th','st','nd','rd','th','th','th','th','th','th'];
                    echo ($i%100>=11 && $i%100<=13) ? 'th' : $suffix[$i%10];
                  ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <input type="hidden" id="regSemester" value=""> <!-- We'll still send a mapped ID for backward compatibility -->
        </div>

        <div class="mb-3">
          <label class="form-label text-secondary">Contact Email</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-envelope"></i></span>
            <input type="email" id="regEmail" class="form-control bg-dark text-white border-secondary border-opacity-25" placeholder="you@university.edu" required/>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label text-secondary">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary"><i class="bi bi-lock"></i></span>
            <input type="password" id="regPassword" class="form-control bg-dark text-white border-secondary border-opacity-25" placeholder="Min. 8 characters" required/>
          </div>
          <div class="mt-1" style="background:rgba(255,255,255,0.05);border-radius:3px;">
            <div id="pwStrengthBar" style="width:0%;height:5px;border-radius:3px;"></div>
          </div>
          <small id="pwStrengthLabel" class="text-secondary opacity-50">Enter password</small>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
          <i class="bi bi-person-plus me-1"></i> Create Account
        </button>
      </form>

      <hr class="my-4 border-secondary border-opacity-25"/>
      <p class="text-center small text-secondary mb-0">
        Already have an account? <a href="../../index.php" class="text-primary text-decoration-none fw-bold">Sign In</a>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../assets/js/auth.js')); ?>"></script>
<script>
  const programsByDept = <?php echo json_encode($programsByDept); ?>;
  const semestersData = <?php echo json_encode($semesters); ?>;

  // Function to find and update semester_id
  function updateSemesterId() {
    const year = document.getElementById('regYear').value;
    const session = document.getElementById('regSession').value;
    const num = document.getElementById('regSemesterNum').value;
    const hiddenInput = document.getElementById('regSemester');

    if (year && session && num) {
      const match = semestersData.find(s => s.year == year && s.session == session && s.number == num);
      hiddenInput.value = match ? match.id : "";
      console.log("Matched Semester ID:", hiddenInput.value);
    } else {
      hiddenInput.value = "";
    }
  }

  document.getElementById('regYear').addEventListener('change', updateSemesterId);
  document.getElementById('regSession').addEventListener('change', updateSemesterId);
  document.getElementById('regSemesterNum').addEventListener('change', updateSemesterId);

  document.getElementById('regDept').addEventListener('change', function () {
    const dept       = this.value;
    const programSel = document.getElementById('regProgram');
    programSel.innerHTML = '<option value="">Select Program</option>';
    if (!dept || !programsByDept[dept]) return;
    programsByDept[dept].forEach(function (p) {
      const opt      = document.createElement('option');
      opt.value      = p.code;
      opt.textContent = p.name;
      programSel.appendChild(opt);
    });
  });

  document.getElementById('regPassword').addEventListener('input', function() {
    const v = this.value;
    const bar = document.getElementById('pwStrengthBar');
    const lbl = document.getElementById('pwStrengthLabel');
    let s = 0;
    if (v.length >= 8) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    const pct = (s / 4) * 100;
    bar.style.width = pct + '%';
    const states = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
    bar.style.background = colors[s] || '';
    lbl.textContent = states[s] || 'Enter password';
    lbl.style.color = colors[s] || '';
  });
</script>
</body>
</html>

