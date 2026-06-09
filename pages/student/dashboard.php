<?php
require_once "../../helpers/auth_check.php";
checkRole('student');
require_once "../../config/db.php";

$student_id = $_SESSION['user_id'];

// Get student's details
$student_query = "
    SELECT u.name, u.roll_number, s.number as semester_number, s.session, s.year 
    FROM users u 
    LEFT JOIN semesters s ON u.semester_id = s.id 
    WHERE u.id = ? 
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$student_name = $student_data['name'] ?? 'Unknown';
$roll_number = $student_data['roll_number'] ?? 'N/A';
$semester_text = isset($student_data['session']) 
    ? $student_data['session'] . " " . $student_data['year'] . " (Sem " . $student_data['semester_number'] . ")"
    : "N/A";

// Get student's group
$group_query = "SELECT g.* FROM `groups` g JOIN group_members gm ON g.id = gm.group_id WHERE gm.student_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $group_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$group_res = mysqli_stmt_get_result($stmt);
$group = mysqli_fetch_assoc($group_res);

$total_proposals = 0;
$approved_proposals = 0;
$under_review = 0;
$active_project = null;

if ($group) {
    $group_id = $group['id'];
    
    // Total proposals
    $prop_query = "SELECT COUNT(*) as count FROM proposals WHERE group_id = ?";
    $stmt = mysqli_prepare($conn, $prop_query);
    mysqli_stmt_bind_param($stmt, "i", $group_id);
    mysqli_stmt_execute($stmt);
    $prop_res = mysqli_stmt_get_result($stmt);
    $total_proposals = mysqli_fetch_assoc($prop_res)['count'];
    
    // Approved
    $app_query = "SELECT COUNT(*) as count FROM proposals WHERE group_id = ? AND status = 'accepted'";
    $stmt = mysqli_prepare($conn, $app_query);
    mysqli_stmt_bind_param($stmt, "i", $group_id);
    mysqli_stmt_execute($stmt);
    $approved_proposals = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
    
    // Under review
    $rev_query = "SELECT COUNT(*) as count FROM proposals WHERE group_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $rev_query);
    mysqli_stmt_bind_param($stmt, "i", $group_id);
    mysqli_stmt_execute($stmt);
    $under_review = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
    
    // Get student's role in group
    $role_query = "SELECT role FROM group_members WHERE group_id = ? AND student_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $role_query);
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $student_id);
    mysqli_stmt_execute($stmt);
    $user_role_in_group = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['role'] ?? 'member';

    // Active project
    if ($group['status'] === 'active' || $group['status'] === 'completed') {
        $active_project = $group['name'];
    }
} else {
    $user_role_in_group = 'none';
}

$pageTitle = "Student Dashboard";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<div class="main">
  <!-- Profile Header Card -->
  <div class="profile-card p-4 mb-5 shadow-lg position-relative" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1;">
      <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 border border-white border-opacity-20 shadow-sm" style="width: 80px; height: 80px; color: white;">
        <?php echo strtoupper(substr($student_name, 0, 1)); ?>
      </div>
      <div>
        <h2 class="mb-1 fw-bold text-white"><?php echo htmlspecialchars($student_name); ?></h2>
        <div class="d-flex flex-wrap gap-3">
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($roll_number); ?>
          </span>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-calendar-event me-2"></i><?php echo htmlspecialchars($semester_text); ?>
          </span>
        </div>
      </div>
    </div>
    <i class="bi bi-mortarboard position-absolute" style="right: 30px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 8rem; opacity: 0.05; color: white; pointer-events: none;"></i>
  </div>

  <?php 
    $headerTitle = "Quick Overview";
    $showNotifications = false; // Students don't have general notifications yet in original code
    $extraButtons = '';
    if ($user_role_in_group === 'leader' && $approved_proposals == 0) {
        $extraButtons = '<a href="submit-proposal.php" class="btn btn-primary rounded-3 px-4 shadow-sm"><i class="bi bi-plus-lg me-2"></i> New Proposal</a>';
    } elseif ($approved_proposals > 0) {
        $extraButtons = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill small"><i class="bi bi-check-circle-fill me-2"></i>Project Approved</span>';
    }
    require_once "../../includes/navbar.php";
  ?>

  <!-- Stats Row -->
  <div class="row g-4 mb-5">
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Total Proposals</div>
        <div class="fw-bold fs-3 text-white"><?php echo $total_proposals; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Accepted</div>
        <div class="fw-bold fs-3 text-white"><?php echo $approved_proposals; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Under Review</div>
        <div class="fw-bold fs-3 text-white"><?php echo $under_review; ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-star-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Recent Score</div>
        <div class="fw-bold fs-3 text-white">--</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card shadow-lg h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white"><i class="bi bi-folder-fill me-2 text-primary"></i>Active Project</span>
          <span class="badge <?php echo $active_project ? 'bg-success' : 'bg-secondary'; ?> bg-opacity-10 <?php echo $active_project ? 'text-success' : 'text-secondary'; ?> border <?php echo $active_project ? 'border-success' : 'border-secondary'; ?> border-opacity-25 rounded-pill px-3">
            <?php echo $active_project ? 'Active' : 'No Project'; ?>
          </span>
        </div>
        <div class="card-body p-4">
          <?php if ($active_project): ?>
            <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($active_project); ?></h5>
            <p class="text-secondary small mb-4">Your group's current PBL project. Keep up the good work!</p>
            <a href="my-projects.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">View Details</a>
          <?php else: ?>
            <div class="text-center py-5">
              <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-folder2-open text-secondary fs-3"></i>
              </div>
              <p class="text-secondary mb-0">No active project assigned yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-lg h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-bold text-white"><i class="bi bi-chat-left-text-fill me-2 text-warning"></i>Recent Feedback</span>
          <a href="feedback.php" class="small text-primary text-decoration-none fw-medium">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush bg-transparent">
            <div class="p-5 text-center">
              <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-chat-dots text-secondary fs-3"></i>
              </div>
              <p class="text-secondary mb-0">No recent feedback received.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
