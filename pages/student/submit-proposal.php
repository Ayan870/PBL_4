<?php
require_once "../../helpers/auth_check.php";
require_once "../../config/db.php";
checkRole('student');
$pageTitle = "Submit Proposal – PROVIA";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";

$user_id = $_SESSION["user_id"];

// Check if student is in an accepted group and get their role
$query_group = "
    SELECT gm.group_id, gm.role,
           (SELECT COUNT(*) FROM proposals WHERE group_id = gm.group_id AND status = 'accepted') as approved_count
    FROM group_members gm
    WHERE gm.student_id = ? AND gm.invite_status = 'accepted'
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $query_group);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$group_res = mysqli_stmt_get_result($stmt);
$group_info = mysqli_fetch_assoc($group_res);

$no_group_error = !$group_info;
$not_leader_error = ($group_info && $group_info['role'] !== 'leader');
$already_approved_error = ($group_info && $group_info['approved_count'] > 0);

// Get flex submission status if exists
$flex_info = null;
if ($group_info) {
    $flex_q = "SELECT status, feedback FROM flex_submissions WHERE group_id = ? ORDER BY created_at DESC LIMIT 1";
    $flex_stmt = mysqli_prepare($conn, $flex_q);
    mysqli_stmt_bind_param($flex_stmt, "i", $group_info['group_id']);
    mysqli_stmt_execute($flex_stmt);
    $flex_info = mysqli_fetch_assoc(mysqli_stmt_get_result($flex_stmt));
}

// Get student's department name
$query_dept = "
    SELECT d.name 
    FROM users u
    JOIN departments d ON u.department_id = d.id
    WHERE u.id = ?
";
$stmt_dept = mysqli_prepare($conn, $query_dept);
mysqli_stmt_bind_param($stmt_dept, "i", $user_id);
mysqli_stmt_execute($stmt_dept);
$dept_res = mysqli_stmt_get_result($stmt_dept);
$student_dept = mysqli_fetch_assoc($dept_res)['name'] ?? '';
?>




<style>
  .view-container { display: none; }
  .view-container.active { display: block; }
  
  .selection-hub { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 20px; }
  .hub-card { 
    background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 40px 30px; 
    text-align: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative; overflow: hidden;
  }
  .hub-card:hover { transform: translateY(-8px); border-color: #6366f1; background: rgba(99, 102, 241, 0.05); }
  .hub-card i { font-size: 3rem; color: #6366f1; margin-bottom: 20px; display: block; }
  .hub-card h3 { color: white; font-weight: 700; margin-bottom: 15px; }
  .hub-card p { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; }
  .hub-card .badge { position: absolute; top: 20px; right: 20px; }

  .btn-back { color: #94a3b8; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 30px; transition: color 0.2s; }
  .btn-back:hover { color: #6366f1; }

  .form-step { display: none; }
  .form-step.active { display: block; }
  
  .step-circle { width: 32px; height: 32px; border-radius: 50%; border: 2px solid #334155; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; color: #94a3b8; }
  .step-circle.active { border-color: #6366f1; background: #6366f1; color: white; }
  .step-line { flex-grow: 1; height: 2px; background: #334155; }
  .step-line.done { background: #6366f1; }

  .file-drop { border: 2px dashed #334155; border-radius: 16px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.2s; }
  .file-drop:hover { border-color: #6366f1; background: rgba(99, 102, 241, 0.03); }
</style>

<div class="main">
  <div class="mb-5" id="pageHeader">
    <h3 class="mb-1 fw-bold text-white">Project Proposal</h3>
    <p class="text-secondary mb-0">Follow the steps below to register your PBL project</p>
  </div>

  <?php if ($no_group_error): ?>
    <div class="card shadow-lg text-center p-5">
      <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-4" style="width: 80px; height: 80px; font-size: 2.5rem;">
        <i class="bi bi-people-fill"></i>
      </div>
      <h4 class="text-white fw-bold">Group Required</h4>
      <p class="text-secondary mx-auto mb-4" style="max-width: 500px;">You must be part of an active project group to submit a proposal. Please form a group or accept a pending invitation first.</p>
      <div>
        <a href="my-projects.php" class="btn btn-primary rounded-pill px-4">Manage Groups</a>
      </div>
    </div>
  <?php elseif ($not_leader_error): ?>
    <div class="card shadow-lg text-center p-5">
      <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-4" style="width: 80px; height: 80px; font-size: 2.5rem;">
        <i class="bi bi-person-badge-fill"></i>
      </div>
      <h4 class="text-white fw-bold">Leader Only</h4>
      <p class="text-secondary mx-auto mb-4" style="max-width: 500px;">Only the group leader is authorized to submit or update the project proposal. Please coordinate with your team leader.</p>
      <div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Back to Dashboard</a>
      </div>
    </div>
  <?php else: ?>
  <!-- Selection View -->
  <div id="selectionHub" class="view-container active">
    <div class="selection-hub">
      <div class="hub-card" onclick="switchView('proposalView')">
        <i class="bi bi-file-earmark-text"></i>
        <h3>Project Proposal</h3>
        <p>Submit your initial project idea, objectives, and methodology for approval.</p>
      </div>

      <div class="hub-card" onclick="switchView('flexView')">
        <span class="badge bg-info text-white border border-white border-opacity-25 fw-bold px-3">END SEMESTER</span>
        <i class="bi bi-file-earmark-zip"></i>
        <h3>Flex Submission</h3>
        <p>Submit your final flex document (PDF or PNG) at the end of the semester.</p>
      </div>
    </div>
  </div>

  <!-- Proposal View -->
  <div id="proposalView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    
    <?php if ($already_approved_error): ?>
      <div class="card shadow-lg text-center p-5">
        <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-4" style="width: 80px; height: 80px; font-size: 2.5rem;">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <h4 class="text-white fw-bold">Proposal Approved</h4>
        <p class="text-secondary mx-auto mb-4" style="max-width: 500px;">Your project proposal has already been approved by your supervisor. You are now in the implementation phase and cannot submit more proposals.</p>
        <div>
          <button class="btn btn-success rounded-pill px-4" onclick="switchView('selectionHub')">Back to Options</button>
        </div>
      </div>
    <?php else: ?>
      <!-- Step Indicator -->
      <div class="card shadow-sm mb-4">
        <div class="card-body py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="step-circle active" id="step-ind-1">1</div>
            <span class="small fw-bold text-white d-none d-md-inline">Basics</span>
            <div class="step-line" id="line-1"></div>
            <div class="step-circle" id="step-ind-2">2</div>
            <span class="small text-secondary d-none d-md-inline">Details</span>
            <div class="step-line" id="line-2"></div>
            <div class="step-circle" id="step-ind-3">3</div>
            <span class="small text-secondary d-none d-md-inline">Documents</span>
          </div>
        </div>
      </div>

      <div class="card shadow-lg">
        <div class="card-body p-4">
          <form id="proposalForm" onsubmit="handleProposalSubmit(event)">
            <!-- ... form content ... -->
          <!-- Step 1: Basic Info -->
          <div class="form-step active" id="fstep-1">
            <h5 class="text-white fw-bold mb-4">Step 1: Project Basic Info</h5>
            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Project Title <span class="text-danger">*</span></label>
              <input type="text" id="projTitle" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="e.g. AI-based Medical Diagnosis System" required/>
            </div>
            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Department <span class="text-secondary">(Locked)</span></label>
              <input type="text" id="projDept" class="form-control bg-dark border-secondary border-opacity-25 text-white-50" value="<?php echo htmlspecialchars($student_dept); ?>" readonly />
              <div class="form-text small text-secondary">Proposals are automatically assigned to your registered department.</div>
            </div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="nextStep(1)">Next Step <i class="bi bi-arrow-right ms-2"></i></button>
            </div>
          </div>

          <!-- Step 2: Details -->
          <div class="form-step" id="fstep-2">
            <h5 class="text-white fw-bold mb-4">Step 2: Project Description</h5>
            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Description / Abstract <span class="text-danger">*</span></label>
              <textarea id="projAbstract" class="form-control bg-dark border-secondary border-opacity-25 text-white" rows="6" placeholder="Provide a detailed overview of your project idea..." required></textarea>
            </div>
            <div class="mb-4">
              <label class="form-label text-secondary small fw-bold">Tools & Technologies</label>
              <input type="text" id="projTools" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="e.g. PHP, MySQL, Python, TensorFlow"/>
            </div>
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="prevStep(2)"><i class="bi bi-arrow-left me-2"></i> Back</button>
              <button type="button" class="btn btn-primary px-4 rounded-pill" onclick="nextStep(2)">Next Step <i class="bi bi-arrow-right ms-2"></i></button>
            </div>
          </div>

          <!-- Step 3: Files -->
          <div class="form-step" id="fstep-3">
            <h5 class="text-white fw-bold mb-4">Step 3: Documentation</h5>
            <div class="file-drop mb-5" id="fileDrop" onclick="document.getElementById('fileInput').click()">
              <input type="file" id="fileInput" accept=".pdf,.doc,.docx" hidden onchange="handleFile(this)"/>
              <div id="fileDropInner">
                <i class="bi bi-cloud-arrow-up-fill text-primary fs-1 mb-3"></i>
                <h5 class="text-white">Upload Proposal Document</h5>
                <p class="text-secondary small">Click to browse or drag & drop PDF/Word file</p>
              </div>
              <div id="filePreview" class="d-none">
                <i class="bi bi-file-earmark-pdf-fill text-danger fs-2 mb-2"></i>
                <div id="fileName" class="text-white fw-bold"></div>
                <div id="fileSize" class="text-secondary small mb-3"></div>
                <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="clearFile(event)">Remove File</button>
              </div>
            </div>
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="prevStep(3)"><i class="bi bi-arrow-left me-2"></i> Back</button>
              <button type="submit" class="btn btn-success px-5 rounded-pill shadow-sm" id="submitBtn">
                <i class="bi bi-send-fill me-2"></i> Submit Proposal
              </button>
            </div>
          </div>
        </form>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Flex View -->
  <div id="flexView" class="view-container">
    <a href="#" class="btn-back" onclick="switchView('selectionHub')"><i class="bi bi-arrow-left"></i> Back to Hub</a>
    
    <div class="card shadow-lg">
      <div class="card-body p-5 text-center">
        <h4 class="text-white fw-bold mb-3">Flex Submission</h4>
        <?php if ($flex_info): ?>
          <div class="mb-4">
            <span class="badge bg-<?php echo $flex_info['status'] === 'pending' ? 'warning' : ($flex_info['status'] === 'accepted' ? 'success' : 'danger'); ?> bg-opacity-10 text-<?php echo $flex_info['status'] === 'pending' ? 'warning' : ($flex_info['status'] === 'accepted' ? 'success' : 'danger'); ?> border border-<?php echo $flex_info['status'] === 'pending' ? 'warning' : ($flex_info['status'] === 'accepted' ? 'success' : 'danger'); ?> border-opacity-25 px-4 py-2 rounded-pill mb-2">
              Status: <?php echo ucfirst($flex_info['status']); ?>
            </span>
            <p class="text-secondary small">You have already submitted a flex document. You can re-upload to update it.</p>
            <?php if ($flex_info['status'] === 'rejected' && $flex_info['feedback']): ?>
              <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small mt-3 text-start mx-auto" style="max-width:500px;">
                <strong>Feedback:</strong> <?php echo htmlspecialchars($flex_info['feedback']); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <p class="text-secondary mb-5">Upload your end-of-semester flex document (PDF or PNG image).</p>
        
        <form id="flexForm" onsubmit="handleFlexSubmit(event)">
          <div class="file-drop mb-5" id="flexFileDrop" onclick="document.getElementById('flexFileInput').click()">
            <input type="file" id="flexFileInput" accept=".pdf,.png" hidden onchange="handleFlexFile(this)"/>
            <div id="flexFileDropInner">
              <i class="bi bi-file-earmark-arrow-up-fill text-info fs-1 mb-3"></i>
              <h5 class="text-white">Upload Flex File</h5>
              <p class="text-secondary small">Click to browse or drag & drop PDF/PNG</p>
            </div>
            <div id="flexFilePreview" class="d-none">
              <i class="bi bi-file-earmark-check-fill text-success fs-2 mb-2"></i>
              <div id="flexFileName" class="text-white fw-bold"></div>
              <div id="flexFileSize" class="text-secondary small mb-3"></div>
              <button type="button" class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="clearFlexFile(event)">Remove File</button>
            </div>
          </div>
          
          <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-info px-5 rounded-pill shadow-sm text-white fw-bold" id="flexSubmitBtn">
              <i class="bi bi-cloud-check-fill me-2"></i> Submit Flex
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js?v=1.1"></script>
<script>
function switchView(viewId) {
  document.querySelectorAll('.view-container').forEach(v => v.classList.remove('active'));
  document.getElementById(viewId).classList.add('active');
  
  // Hide page header if not in selection hub
  const header = document.getElementById('pageHeader');
  if (viewId === 'selectionHub') {
    header.style.display = 'block';
  } else {
    header.style.display = 'none';
  }
}

function handleFlexFile(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('flexFileDropInner').classList.add('d-none');
    document.getElementById('flexFilePreview').classList.remove('d-none');
    document.getElementById('flexFileName').textContent = file.name;
    document.getElementById('flexFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
  }
}

function clearFlexFile(e) {
  e.stopPropagation();
  document.getElementById('flexFileInput').value = '';
  document.getElementById('flexFileDropInner').classList.remove('d-none');
  document.getElementById('flexFilePreview').classList.add('d-none');
}

async function handleFlexSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('flexSubmitBtn');
  const fileInput = document.getElementById('flexFileInput');
  
  if (fileInput.files.length === 0) {
    alert('Please select a file to upload.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

  const formData = new FormData();
  formData.append('flex_file', fileInput.files[0]);

  try {
    const res = await fetch('../../api/student/submit_flex.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      window.location.href = 'dashboard.php';
    } else {
      alert('Error: ' + data.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cloud-check-fill me-2"></i> Submit Flex';
    }
  } catch (err) {
    alert('Connection failed. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-check-fill me-2"></i> Submit Flex';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  requireAuth('student');
});

function nextStep(current) {
  document.getElementById(`fstep-${current}`).classList.remove('active');
  document.getElementById(`fstep-${current+1}`).classList.add('active');
  document.getElementById(`step-ind-${current+1}`).classList.add('active');
  document.getElementById(`line-${current}`).classList.add('done');
}

function prevStep(current) {
  document.getElementById(`fstep-${current}`).classList.remove('active');
  document.getElementById(`fstep-${current-1}`).classList.add('active');
  document.getElementById(`step-ind-${current}`).classList.remove('active');
  document.getElementById(`line-${current-1}`).classList.remove('done');
}

function handleFile(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('fileDropInner').classList.add('d-none');
    document.getElementById('filePreview').classList.remove('d-none');
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
  }
}

function clearFile(e) {
  e.stopPropagation();
  document.getElementById('fileInput').value = '';
  document.getElementById('fileDropInner').classList.remove('d-none');
  document.getElementById('filePreview').classList.add('d-none');
}

async function handleProposalSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

  const formData = new FormData();
  formData.append('title', document.getElementById('projTitle').value);
  formData.append('abstract', document.getElementById('projAbstract').value);
  formData.append('tools', document.getElementById('projTools').value);
  
  const fileInput = document.getElementById('fileInput');
  if (fileInput.files.length > 0) {
    formData.append('proposal_file', fileInput.files[0]);
  }

  try {
    const res = await fetch('../../api/student/submit_proposal.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      alert('Proposal submitted successfully!');
      window.location.href = 'dashboard.php';
    } else {
      alert('Error: ' + data.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send-fill me-2"></i> Submit Proposal';
    }
  } catch (err) {
    alert('Connection failed. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill me-2"></i> Submit Proposal';
  }
}
</script>
<?php require_once "../../includes/footer.php"; ?>
</body>
</html>

