<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

$pageTitle = "PBL Subject Assignment";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h3 class="mb-1 fw-bold text-white">PBL Subject Assignment</h3>
      <p class="text-secondary mb-0">Decide the PBL subject for each class in your department</p>
    </div>
  </div>

  <div class="card shadow-lg">
    <div class="card-header py-3">
      <span class="fw-bold text-white">Class List</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">Program</th>
              <th>Semester</th>
              <th>Section</th>
              <th>Current PBL Subject</th>
              <th class="pe-4 text-end">Action</th>
            </tr>
          </thead>
          <tbody id="classList">
            <tr><td colspan="5" class="text-center py-5">Loading classes...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white">Assign PBL Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4">
        <div class="mb-4 text-center">
            <div class="text-secondary small">Assigning subject for:</div>
            <div class="fw-bold text-white fs-5" id="modalClassLabel"></div>
        </div>
        <div class="mb-3">
          <label class="form-label text-secondary small fw-bold text-uppercase">Select Subject</label>
          <select class="form-select bg-dark border-secondary border-opacity-50 text-white" id="subjectSelect">
            <option value="">Choose a subject...</option>
          </select>
        </div>
        <div class="text-secondary smaller">
            <i class="bi bi-info-circle me-1"></i> Once assigned, students in this class will see this subject when forming groups.
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary px-4" id="btnSaveAssign">Save Assignment</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/auth.js"></script>
<script src="../../assets/js/app.js"></script>
<script>
let classes = [];
let subjects = [];
let selectedClassId = null;

async function loadData() {
    try {
        const res = await fetch('../../api/manager/get_subject_assignment_data.php');
        if (!res.ok) throw new Exception('Network response was not ok');
        const data = await res.json();
        if (data.success) {
            classes = data.classes;
            subjects = data.subjects;
            renderClasses();
            populateSubjects();
        } else {
            document.getElementById('classList').innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger">Error: ${data.message}</td></tr>`;
        }
    } catch (e) {
        console.error(e);
        document.getElementById('classList').innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger">Failed to load data. Please ensure the database is up to date.</td></tr>`;
    }
}

function renderClasses() {
    const tbody = document.getElementById('classList');
    if (classes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-secondary">No classes found in your department.</td></tr>';
        return;
    }
    tbody.innerHTML = classes.map(c => `
        <tr>
            <td class="ps-4 py-3">
                <div class="fw-bold text-white">${c.program_name}</div>
            </td>
            <td>Semester ${c.semester_number}</td>
            <td>Section ${c.section}</td>
            <td>
                ${c.pbl_subject_title ? 
                    `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 rounded-pill">${c.pbl_subject_title}</span>` : 
                    '<span class="text-danger small italic">Not Assigned</span>'}
            </td>
            <td class="pe-4 text-end">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openAssignModal(${c.id}, '${c.program_name} Sem ${c.semester_number} - ${c.section}')">
                    <i class="bi bi-pencil-square me-1"></i> ${c.pbl_subject_title ? 'Change' : 'Assign'}
                </button>
            </td>
        </tr>
    `).join('');
}

function populateSubjects() {
    const select = document.getElementById('subjectSelect');
    select.innerHTML = '<option value="">Choose a subject...</option>';
    subjects.forEach(s => {
        select.innerHTML += `<option value="${s.title}">${s.title}</option>`;
    });
}

function openAssignModal(id, label) {
    selectedClassId = id;
    document.getElementById('modalClassLabel').textContent = label;
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

document.getElementById('btnSaveAssign').addEventListener('click', async () => {
    const subject = document.getElementById('subjectSelect').value;
    if (!subject) {
        alert('Please select a subject');
        return;
    }

    const btn = document.getElementById('btnSaveAssign');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
        const res = await fetch('../../api/manager/assign_pbl_subject.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ class_id: selectedClassId, subject_title: subject })
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
            loadData();
        } else {
            alert(data.message);
        }
    } catch (e) {
        alert('An error occurred');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Save Assignment';
    }
});

document.addEventListener('DOMContentLoaded', loadData);
</script>

<?php require_once "../../includes/footer.php"; ?>
