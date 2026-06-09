<?php
require_once "../../helpers/auth_check.php";
checkRole('pbl_manager');
require_once "../../config/db.php";

// Manager's details
$manager_id = $_SESSION['user_id'];
$manager_query = "
    SELECT u.name, u.roll_number, u.department_id, d.name as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ? 
    LIMIT 1
";
$stmt = mysqli_prepare($conn, $manager_query);
mysqli_stmt_bind_param($stmt, "i", $manager_id);
mysqli_stmt_execute($stmt);
$manager_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$manager_name = $manager_data['name'] ?? 'Unknown';
$roll_number = $manager_data['roll_number'] ?? 'N/A';
$dept_id = $manager_data['department_id'] ?? 0;
$dept_name = $manager_data['department_name'] ?? 'All';

// Scoped stats
$students_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student' AND department_id = $dept_id"))['count'] ?? 0;
$supervisors_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'supervisor' AND department_id = $dept_id"))['count'] ?? 0;

$total_proposals_query = "
    SELECT COUNT(pr.id) as count 
    FROM proposals pr
    JOIN `groups` g ON pr.group_id = g.id
    JOIN classes c ON g.class_id = c.id
    JOIN programs p ON c.program_id = p.id
    WHERE p.department_id = $dept_id
";
$total_proposals = mysqli_fetch_assoc(mysqli_query($conn, $total_proposals_query))['count'];
$approved_count = mysqli_fetch_assoc(mysqli_query($conn, $total_proposals_query . " AND pr.status = 'accepted'"))['count'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, $total_proposals_query . " AND pr.status = 'pending'"))['count'];

$prog_query = "
    SELECT 
        p.name, 
        (SELECT COUNT(*) FROM users u WHERE u.program_id = p.id AND u.role = 'student') as total_students,
        (SELECT COUNT(DISTINCT gm.student_id) 
         FROM group_members gm 
         JOIN `groups` g ON gm.group_id = g.id 
         JOIN proposals pr ON g.id = pr.group_id
         JOIN classes c ON g.class_id = c.id
         WHERE c.program_id = p.id AND gm.invite_status = 'accepted') as submitted_students
    FROM programs p
    WHERE p.department_id = $dept_id
    ORDER BY p.name ASC
";
$prog_res = mysqli_query($conn, $prog_query);
$progs = [];
if ($prog_res) {
    while ($row = mysqli_fetch_assoc($prog_res)) {
        $progs[] = $row;
    }
}

$pageTitle = "Manager Dashboard";
require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
?>

<div class="main">
  <!-- Profile Header Card -->
  <div class="profile-card p-4 mb-5 shadow-lg position-relative" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
    <div class="d-flex flex-wrap align-items-center gap-4 position-relative" style="z-index: 1;">
      <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 border border-white border-opacity-20 shadow-sm" style="width: 80px; height: 80px; color: white;">
        <?php echo strtoupper(substr($manager_name, 0, 1)); ?>
      </div>
      <div>
        <h2 class="mb-1 fw-bold text-white"><?php echo htmlspecialchars($manager_name); ?></h2>
        <div class="d-flex flex-wrap gap-3">
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($roll_number); ?>
          </span>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-building me-2"></i><?php echo htmlspecialchars($dept_name); ?>
          </span>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #e0e7ff;">
            <i class="bi bi-shield-check me-2"></i>PBL Manager
          </span>
        </div>
      </div>
    </div>
    <i class="bi bi-shield-lock position-absolute" style="right: 30px; top: 50%; transform: translateY(-50%) rotate(-15deg); font-size: 8rem; opacity: 0.05; color: white; pointer-events: none;"></i>
  </div>

  <?php 
    $headerTitle = "Manager Overview";
    $headerSubtitle = "Monitor PBL activities and department performance";
    $showNotifications = false;
    $extraButtons = '<button class="btn btn-primary rounded-3 px-4 shadow-sm" onclick="alert(\'Export functionality coming soon!\')"><i class="bi bi-download me-2"></i> Export Report</button>';
    require_once "../../includes/navbar.php";
  ?>

  <div class="row g-4 mb-5">
    <div class="col-md col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Students</div>
        <div class="fw-bold fs-3 text-white"><?php echo $students_count; ?></div>
      </div>
    </div>
    <div class="col-md col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-person-badge-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Supervisors</div>
        <div class="fw-bold fs-3 text-white"><?php echo $supervisors_count; ?></div>
      </div>
    </div>
    <div class="col-md col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Proposals</div>
        <div class="fw-bold fs-3 text-white"><?php echo $total_proposals; ?></div>
      </div>
    </div>
    <div class="col-md col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Approved</div>
        <div class="fw-bold fs-3 text-white"><?php echo $approved_count; ?></div>
      </div>
    </div>
    <div class="col-md col-6">
      <div class="stat-card shadow-sm">
        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-circle-fill"></i></div>
        <div class="text-secondary small fw-bold text-uppercase mb-1">Pending</div>
        <div class="fw-bold fs-3 text-white"><?php echo $pending_count; ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-5">
      <div class="card shadow-lg h-100">
        <div class="card-header">
          <span class="fw-bold text-white">Program Performance</span>
        </div>
        <div class="card-body p-4">
          <?php if (empty($progs)): ?>
            <p class="text-secondary small text-center my-4">No programs found for this department.</p>
          <?php else: ?>
              <?php 
              $colors = ['bg-primary', 'bg-info', 'bg-success', 'bg-warning', 'bg-indigo-500', 'bg-danger'];
              foreach ($progs as $idx => $p): 
                $color = $colors[$idx % count($colors)];
                $total = (int)$p['total_students'];
                $sub   = (int)$p['submitted_students'];
                $pct   = ($total > 0) ? round(($sub / $total) * 100) : 0;
              ?>
                <div class="mb-4">
                  <div class="d-flex justify-content-between small mb-2">
                    <span class="text-white fw-medium"><?php echo htmlspecialchars($p['name']); ?></span>
                    <span class="text-secondary"><?php echo $pct; ?>% <small>(<?php echo $sub; ?>/<?php echo $total; ?>)</small></span>
                  </div>
                  <div class="progress bg-secondary bg-opacity-10" style="height:8px; border-radius: 4px;">
                    <div class="progress-bar <?php echo $color; ?> rounded-pill" style="width:<?php echo $pct; ?>%"></div>
                  </div>
                </div>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card shadow-lg h-100">
        <div class="card-header">
          <span class="fw-bold text-white">Department Overview</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-dark bg-opacity-50">
                <tr><th class="ps-4">#</th><th>Project Title</th><th>Program</th><th>Status</th></tr>
              </thead>
              <tbody>
                <tr><td colspan="4" class="text-center py-5 text-secondary">No active projects to display.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
