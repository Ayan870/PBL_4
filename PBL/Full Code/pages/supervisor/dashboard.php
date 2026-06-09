<?php
require_once "../../helpers/auth_check.php";
checkRole('supervisor');
require_once "../../config/db.php";

$supervisor_id = $_SESSION['user_id'];
$dept_id = $_SESSION['user_dept_id'] ?? 0;

// Supervisor's details
$supervisor_query = "
    SELECT u.name, u.roll_number, u.department_id, d.name as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ? 
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $supervisor_query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$supervisor_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$supervisor_name = $supervisor_data['name'] ?? 'Unknown';
$roll_number = $supervisor_data['roll_number'] ?? 'N/A';
$dept_name = $supervisor_data['department_name'] ?? 'N/A';
$dept_id = $supervisor_data['department_id'] ?? $dept_id;

// Get classes assigned to this supervisor
$classes_query = "SELECT class_id FROM class_supervisors WHERE supervisor_id = ?";
$stmt = mysqli_prepare($conn, $classes_query);
mysqli_stmt_bind_param($stmt, "i", $supervisor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$class_ids = [];
while ($row = mysqli_fetch_assoc($res)) {
    $class_ids[] = $row['class_id'];
}

$ps_filter = "";
if (!empty($class_ids)) {
    $ids_str = implode(',', $class_ids);
    $ps_query = "SELECT DISTINCT program_id, semester_id FROM classes WHERE id IN ($ids_str)";
    $res_ps = mysqli_query($conn, $ps_query);
    $ps_conds = [];
    while ($row = mysqli_fetch_assoc($res_ps)) {
        $ps_conds[] = "(u.program_id = " . $row['program_id'] . " AND u.semester_id = " . $row['semester_id'] . ")";
    }
    if (!empty($ps_conds)) {
        $ps_filter = implode(' OR ', $ps_conds);
    }
}

// Build where clause for students and proposals
if ($ps_filter) {
    $student_where = "u.role = 'student' AND ($ps_filter)";
    $group_where = "g.class_id IN (" . implode(',', $class_ids) . ")";
} else {
    $student_where = "u.role = 'student' AND u.department_id = $dept_id";
    $group_where = "g.pbl_subject_id IN (SELECT id FROM pbl_subjects WHERE program_id IN (SELECT id FROM programs WHERE department_id = $dept_id))";
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT u.id) as count FROM users u WHERE $student_where"))['count'];
$pending_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals p JOIN `groups` g ON p.group_id = g.id WHERE $group_where AND p.status = 'pending'"))['count'];
$approved_projects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals p JOIN `groups` g ON p.group_id = g.id WHERE $group_where AND p.status = 'accepted'"))['count'];

$recent_proposals = [];
$recent_query = "SELECT p.*, g.name as group_name, u.name as leader_name 
                FROM proposals p 
                JOIN `groups` g ON p.group_id = g.id 
                JOIN group_members gm ON g.id = gm.group_id AND gm.role = 'leader'
                JOIN users u ON gm.student_id = u.id
                WHERE $group_where 
                ORDER BY p.submitted_at DESC LIMIT 5";
$recent_res = mysqli_query($conn, $recent_query);
if ($recent_res) {
    while ($row = mysqli_fetch_assoc($recent_res)) {
        $recent_proposals[] = $row;
    }
}

$is_mid_evaluator = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM mid_eval_sessions WHERE evaluator_id = $supervisor_id AND eval_date = CURDATE() AND CURTIME() BETWEEN start_time AND end_time AND status = 'active' LIMIT 1")) > 0;

$pageTitle = "Supervisor Dashboard";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<div class="main">
  <!-- Profile Header Card -->
  <div class="profile-card p-4 mb-5 shadow-lg position-relative" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1;">
      <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 border border-white border-opacity-20 shadow-sm" style="width: 80px; height: 80px; color: white;">
        <?php echo strtoupper(substr($supervisor_name, 0, 1)); ?>
      </div>
      <div>
        <h2 class="mb-1 fw-bold text-white"><?php echo htmlspecialchars($supervisor_name); ?></h2>
        <div class="d-flex flex-wrap gap-3">
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($roll_number); ?>
          </span>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-building me-2"></i><?php echo htmlspecialchars($dept_name); ?>
          </span>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-person-workspace me-2"></i>Supervisor
          </span>
        </div>
      </div>
    </div>
    <i class="bi bi-briefcase position-absolute" style="right: 30px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 8rem; opacity: 0.05; color: white; pointer-events: none;"></i>
  </div>

  <?php 
    $headerTitle = "Supervisor Dashboard";
    $headerSubtitle = "Manage project proposals and student progress";
    $showNotifications = true;
    $extraButtons = '<a href="evaluation.php" class="btn btn-primary rounded-3 px-4 shadow-sm"><i class="bi bi-star-fill me-2"></i> New Evaluation</a>';
    require_once "../../includes/navbar.php";
  ?>

  <div class="row g-4 mb-5">
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Students</div>
        <div class="fw-bold fs-3 text-white"><?php echo $total_students; ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Pending Reviews</div>
        <div class="fw-bold fs-3 text-white"><?php echo $pending_reviews; ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Approved Projects</div>
        <div class="fw-bold fs-3 text-white"><?php echo $approved_projects; ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-award-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Recent Evaluations</div>
        <div class="fw-bold fs-3 text-white">--</div>
      </div>
    </div>
  </div>

  <div class="card shadow-lg">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-bold text-white">Pending Proposals</span>
      <a href="review-proposals.php" class="btn btn-link btn-sm text-primary text-decoration-none p-0 fw-medium">View All</a>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-dark bg-opacity-50">
            <tr>
              <th class="ps-4">Project Title</th>
              <th>Student Leader</th>
              <th>Submitted</th>
              <th>Status</th>
              <th class="pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_proposals)): ?>
                <tr><td colspan="5" class="text-center py-5 text-secondary">No pending proposals yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recent_proposals as $prop): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-white"><?php echo htmlspecialchars($prop['title']); ?></div>
                            <small class="text-secondary">PBL Project</small>
                        </td>
                        <td><span class="text-secondary small"><?php echo htmlspecialchars($prop['leader_name']); ?></span></td>
                        <td><span class="text-secondary small"><?php echo date('M d, Y', strtotime($prop['submitted_at'])); ?></span></td>
                        <td>
                          <span class="badge bg-<?php echo $prop['status'] === 'pending' ? 'warning' : ($prop['status'] === 'accepted' ? 'success' : 'danger'); ?> bg-opacity-10 text-<?php echo $prop['status'] === 'pending' ? 'warning' : ($prop['status'] === 'accepted' ? 'success' : 'danger'); ?> border border-<?php echo $prop['status'] === 'pending' ? 'warning' : ($prop['status'] === 'accepted' ? 'success' : 'danger'); ?> border-opacity-25 px-3 rounded-pill">
                            <?php echo ucfirst($prop['status']); ?>
                          </span>
                        </td>
                        <td class="pe-4">
                            <a href="review-proposals.php?id=<?php echo $prop['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Review</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
